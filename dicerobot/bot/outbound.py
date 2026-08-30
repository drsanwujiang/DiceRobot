"""被动回复的配额管理。

平台要求回复携带来源的 ``msg_id`` 或 ``event_id``，并受两重限制：

============  ==========  ========
场景          时间窗口    最多条数
============  ==========  ========
群聊          5 分钟      5
单聊          60 分钟     4
============  ==========  ========

:class:`ReplySession` 负责计量，:class:`ReplyBuffer` 把一次指令执行期间的多段输出
合并为一条消息，以减少消耗。
"""

from __future__ import annotations

from collections.abc import Callable, Mapping
from dataclasses import dataclass
from datetime import UTC, datetime, timedelta

from loguru import logger

from dicerobot.bot.message import ReplyTarget
from dicerobot.enums import Scene
from dicerobot.errors import ReplyQuotaExhaustedError, ReplyWindowExpiredError
from dicerobot.qq.client import QQClient

__all__ = ["QUOTAS", "Quota", "ReplyBuffer", "ReplySession"]


@dataclass(frozen=True, slots=True)
class Quota:
    """某一场景下的被动回复限制。"""

    window: timedelta
    max_replies: int


QUOTAS: Mapping[Scene, Quota] = {
    Scene.GROUP: Quota(window=timedelta(minutes=5), max_replies=5),
    Scene.C2C: Quota(window=timedelta(minutes=60), max_replies=4),
}


class ReplySession:
    """一个来源的回复配额，按事件独占，不可跨事件复用。

    来源可以是消息，也可以是机器人被加入群聊等事件；两者的差别只在于回传的是
    ``msg_id`` 还是 ``event_id``，配额按场景计量，与来源无关。
    """

    def __init__(
        self,
        *,
        client: QQClient,
        target: ReplyTarget,
        quota: Quota | None = None,
        now: Callable[[], datetime] = lambda: datetime.now(UTC),
    ) -> None:
        self._client = client
        self._target = target
        self._quota = quota if quota is not None else QUOTAS[target.scene]
        self._now = now
        self._sent = 0

    @property
    def remaining(self) -> int:
        """剩余可用的回复条数。"""

        return max(0, self._quota.max_replies - self._sent)

    @property
    def expired(self) -> bool:
        """是否已超出回复时间窗口。"""

        return self._now() - self._target.received_at >= self._quota.window

    async def send(self, content: str) -> None:
        """发送一条被动回复。

        序号在实际发出之前递增，因此失败也会消耗配额。发送失败时无法确定消息是否
        已送达，复用同一序号会被平台判为重复，换用新序号则可能产生重复消息。

        Raises:
            ReplyWindowExpiredError: 已超出时间窗口。
            ReplyQuotaExhaustedError: 回复条数已用尽。
        """

        if self.expired:
            raise ReplyWindowExpiredError(f"已超出 {self._target.scene} 场景 {self._quota.window} 的被动回复窗口")

        if self.remaining == 0:
            raise ReplyQuotaExhaustedError(f"{self._target.scene} 场景的 {self._quota.max_replies} 条被动回复已用尽")

        self._sent += 1
        msg_seq = self._sent

        if self._target.scene is Scene.GROUP:
            await self._client.send_group_message(
                group_openid=self._target.scene_id,
                content=content,
                msg_seq=msg_seq,
                msg_id=self._target.msg_id,
                event_id=self._target.event_id,
            )
        else:
            await self._client.send_c2c_message(
                openid=self._target.scene_id,
                content=content,
                msg_seq=msg_seq,
                msg_id=self._target.msg_id,
                event_id=self._target.event_id,
            )

        logger.debug("已回复 {}（seq={}，剩余 {} 条）", self._target.scene, msg_seq, self.remaining)


class ReplyBuffer:
    """累积输出，在指令执行结束时合并为一条消息发出。

    指令实现调用 :meth:`write` 即可，无需自行拼接换行或关注剩余配额。需要即时反馈时
    显式调用 :meth:`flush`。
    """

    def __init__(self, session: ReplySession) -> None:
        self._session = session
        self._lines: list[str] = []

    @property
    def pending(self) -> bool:
        """是否有尚未发出的内容。"""

        return bool(self._lines)

    def write(self, text: str) -> None:
        """追加一段输出。空白内容会被忽略。"""

        if text and text.strip():
            self._lines.append(text)

    def clear(self) -> None:
        self._lines.clear()

    async def flush(self) -> bool:
        """把已累积的内容合并发出。

        Returns:
            实际发送返回 ``True``；缓冲区为空时不发送并返回 ``False``，以免消耗配额。
        """

        if not self._lines:
            logger.debug("回复缓冲为空，跳过发送")
            return False

        content = "\n".join(self._lines)
        self._lines.clear()
        await self._session.send(content)

        return True
