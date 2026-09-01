"""指令测试的公共 fixture。

指令直接操作 ORM 对象，而列的默认值由数据库在插入时生成，因此测试使用真实的
SQLite 文件而非手工构造的模型实例，以免默认值缺失导致测试与实际行为脱节。
"""

from __future__ import annotations

from collections.abc import AsyncIterator
from datetime import UTC, datetime
from pathlib import Path
from typing import Any, cast

import pytest

from dicerobot.bot.context import CommandContext
from dicerobot.bot.message import IncomingMessage
from dicerobot.bot.outbound import ReplyBuffer, ReplySession
from dicerobot.bot.plugin import CommandHandler, Plugin
from dicerobot.enums import MemberRole, Scene
from dicerobot.qq.client import QQClient
from dicerobot.storage import Base, Chat, Database, Member, Store

CHAT_OPENID = "G1"
MEMBER_OPENID = "USER0001"


class RecordingClient:
    """记录调用参数的替身客户端。"""

    def __init__(self) -> None:
        self.calls: list[dict[str, Any]] = []

    async def send_group_message(self, **kwargs: Any) -> None:
        self.calls.append(kwargs)

    async def send_c2c_message(self, **kwargs: Any) -> None:
        self.calls.append(kwargs)


class CommandRunner:
    """在真实数据库上执行指令，并取回发出的消息。

    每次执行都重新读取插件状态，因此指令通过 ``save_*`` 写回的设置在下一次执行中
    可见——这正是设置持久化要验证的行为。
    """

    def __init__(self, store: Store, chat: Chat, member: Member, plugin: Plugin) -> None:
        self.store = store
        self.chat = chat
        self.member = member
        self.plugin = plugin
        self.client = RecordingClient()

    async def run(
        self,
        handler: CommandHandler,
        args: str = "",
        *,
        name: str = "",
        times: int = 1,
        username: str = "",
        role: MemberRole = MemberRole.MEMBER,
        scene: Scene = Scene.GROUP,
    ) -> str:
        """执行一次指令，返回最终在会话中发出的消息内容。未发出任何内容时返回空串。

        ``scene`` 只改变消息本身，chat 与 member 仍是群内的记录，故它只适用于在场景判断处
        即返回的用例。
        """

        message = IncomingMessage(
            scene=scene,
            scene_id=CHAT_OPENID if scene is Scene.GROUP else MEMBER_OPENID,
            sender_id=MEMBER_OPENID,
            content=f".{name} {args}".strip(),
            message_id="MSG_1",
            received_at=datetime.now(UTC),
            username=username,
            role=role,
        )
        buffer = ReplyBuffer(ReplySession(client=cast(QQClient, self.client), target=message.reply_target))

        await handler(
            CommandContext(
                message=message,
                name=name,
                args=args,
                times=times,
                buffer=buffer,
                chat=self.chat,
                member=self.member,
                plugin_state=await self.store.get_plugin_state(self.plugin.name),
                chat_plugin_state=await self.store.get_chat_plugin_state(Scene.GROUP, CHAT_OPENID, self.plugin.name),
                store=self.store,
            )
        )
        await buffer.flush()

        return str(self.client.calls[-1]["content"]) if self.client.calls else ""


@pytest.fixture
async def database(tmp_path: Path) -> AsyncIterator[Database]:
    """指向临时文件的数据库。

    不用 ``:memory:``：连接池中的每条连接会各自得到一个独立的内存库。
    """

    instance = Database(f"sqlite+aiosqlite:///{tmp_path.as_posix()}/test.db")

    async with instance.engine.begin() as connection:
        await connection.run_sync(Base.metadata.create_all)

    yield instance

    await instance.dispose()


@pytest.fixture
async def store(database: Database) -> AsyncIterator[Store]:
    async with database.session() as session:
        yield Store(session)


@pytest.fixture
def make_runner(store: Store) -> Any:
    """构造针对某个插件的执行器。

    插件状态按插件隔离，因此执行器必须知道自己代表哪个插件。
    """

    async def factory(plugin: Plugin) -> CommandRunner:
        chat = await store.get_chat(Scene.GROUP, CHAT_OPENID)
        member = await store.get_member(Scene.GROUP, CHAT_OPENID, MEMBER_OPENID)

        return CommandRunner(store, chat, member, plugin)

    return factory
