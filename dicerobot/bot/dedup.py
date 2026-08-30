"""事件幂等去重。

平台在未及时收到确认时会重推同一事件。被动回复的 ``msg_id`` 与 ``msg_seq`` 组合
一旦重复即被拒绝，换用新序号则会产生重复回复，因此去重是必需环节。
"""

from __future__ import annotations

from collections import OrderedDict
from collections.abc import Callable
from datetime import UTC, datetime, timedelta

__all__ = ["EventDeduplicator"]


class EventDeduplicator:
    """记录近期见过的事件 ID。

    以 ``OrderedDict`` 维护插入顺序，同时支持按时间过期与按容量淘汰：前者应对正常
    的重推间隔，后者约束事件量突增时的内存占用。
    """

    def __init__(
        self,
        *,
        ttl: timedelta = timedelta(minutes=10),
        max_size: int = 10_000,
        now: Callable[[], datetime] = lambda: datetime.now(UTC),
    ) -> None:
        """
        Args:
            ttl: 记录保留时长，应覆盖平台的重推间隔。
            max_size: 记录条数上限，超出时淘汰最早的记录。
            now: 取当前时间的可调用对象，供测试注入假时钟。
        """

        self._ttl = ttl
        self._max_size = max_size
        self._now = now
        self._seen: OrderedDict[str, datetime] = OrderedDict()

    def is_new(self, event_id: str) -> bool:
        """判断事件是否首次出现，并登记之。

        Returns:
            首次出现返回 ``True``，重复出现返回 ``False``。
        """

        now = self._now()
        self._evict(now)

        if event_id in self._seen:
            return False

        self._seen[event_id] = now

        while len(self._seen) > self._max_size:
            self._seen.popitem(last=False)

        return True

    def _evict(self, now: datetime) -> None:
        deadline = now - self._ttl

        while self._seen:
            _, recorded_at = next(iter(self._seen.items()))

            if recorded_at > deadline:
                break

            self._seen.popitem(last=False)

    def __len__(self) -> int:
        return len(self._seen)
