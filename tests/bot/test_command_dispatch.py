"""指令派发中的发送顺序与私聊投递。

回复须在数据库会话提交之后发出：一次平台调用约 700 ms，横跨事务会让其他 worker 的提交
一直等在 SQLite 的写锁上。私聊排在会话内的回复之前，其失败才来得及在回复中提示。
"""

from __future__ import annotations

from typing import Any, cast

from dicerobot.bot.context import CommandContext
from dicerobot.bot.pipeline import Pipeline
from dicerobot.bot.plugin import Plugin
from dicerobot.bot.registry import Registry
from dicerobot.config import BotSettings
from dicerobot.enums import Scene
from dicerobot.errors import ApiError
from dicerobot.qq.client import QQClient
from dicerobot.qq.schemas import Payload
from dicerobot.storage import Database, Store

NICKNAME = "阿三"

MESSAGE = Payload(
    op=0,
    id="EVENT_1",
    t="GROUP_AT_MESSAGE_CREATE",
    d={"id": "MSG_1", "group_openid": "G1", "author": {"member_openid": "U1"}, "content": ".nn"},
)


class CommitCheckingClient:
    """发送时另开一个会话读取记录，以此判断指令的改动是否已经提交。"""

    def __init__(self, database: Database) -> None:
        self._database = database
        self.nickname_when_sent: str | None = None

    async def send_group_message(self, **kwargs: Any) -> None:
        async with self._database.session() as session:
            member = await Store(session).get_member(Scene.GROUP, "G1", "U1")
            self.nickname_when_sent = member.nickname

    async def send_c2c_message(self, **kwargs: Any) -> None:
        raise AssertionError("群消息不应走单聊接口")


def rename_plugin() -> Plugin:
    plugin = Plugin(name="rename", display_name="rename")

    @plugin.command("nn")
    async def rename(context: CommandContext) -> None:
        context.member.nickname = NICKNAME
        context.write("已改名")

    return plugin


async def test_reply_is_sent_after_the_session_commits(database: Database) -> None:
    registry = Registry()
    registry.add(rename_plugin())

    client = CommitCheckingClient(database)
    pipeline = Pipeline(
        registry=registry,
        client=cast(QQClient, client),
        database=database,
        settings=BotSettings(workers=1),
    )
    await pipeline.start()

    try:
        pipeline.submit(MESSAGE)
    finally:
        await pipeline.stop()

    assert client.nickname_when_sent == NICKNAME


class RefusingClient:
    """拒绝一切私聊投递的替身客户端，模拟用户关闭了主动消息。"""

    def __init__(self) -> None:
        self.replies: list[str] = []

    async def send_group_message(self, **kwargs: Any) -> None:
        self.replies.append(str(kwargs["content"]))

    async def send_c2c_message(self, **kwargs: Any) -> None:
        raise ApiError(code=304003, message="user not allow proactive message", status_code=200)


def hidden_plugin() -> Plugin:
    plugin = Plugin(name="hidden", display_name="hidden")

    @plugin.command("h")
    async def hide(context: CommandContext) -> None:
        context.write_private("只有你能看到的结果")
        context.write("已暗骰")

    return plugin


async def test_a_failed_private_delivery_is_reported_in_the_reply(database: Database) -> None:
    """用户可在客户端关闭主动消息，此时群里若毫无提示，发起者会以为暗骰成功了。"""

    registry = Registry()
    registry.add(hidden_plugin())

    client = RefusingClient()
    pipeline = Pipeline(
        registry=registry,
        client=cast(QQClient, client),
        database=database,
        settings=BotSettings(workers=1),
    )
    await pipeline.start()

    try:
        pipeline.submit(
            Payload(
                op=0,
                id="EVENT_2",
                t="GROUP_AT_MESSAGE_CREATE",
                d={"id": "MSG_2", "group_openid": "G1", "author": {"member_openid": "U1"}, "content": ".h"},
            )
        )
    finally:
        await pipeline.stop()

    assert len(client.replies) == 1
    assert client.replies[0].startswith("已暗骰")
    assert "私聊消息发送失败" in client.replies[0]
