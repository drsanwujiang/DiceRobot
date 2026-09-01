"""持久化层的测试。"""

from __future__ import annotations

import asyncio
from contextlib import suppress
from typing import Any

import pytest
from sqlalchemy import func, select, text

from dicerobot.enums import Scene
from dicerobot.storage import Chat, Database, Store


class TestGetChat:
    async def test_creates_on_first_sight(self, database: Database) -> None:
        """平台不提供会话列表，记录只能由消息本身触发创建。"""

        async with database.session() as session:
            chat = await Store(session).get_chat(Scene.GROUP, "G1")

            assert chat.openid == "G1"
            assert chat.enabled is True

    async def test_returns_the_same_row_afterwards(self, database: Database) -> None:
        async with database.session() as session:
            await Store(session).get_chat(Scene.GROUP, "G1")

        async with database.session() as session:
            chat = await Store(session).get_chat(Scene.GROUP, "G1")
            chat.enabled = False

        async with database.session() as session:
            assert (await Store(session).get_chat(Scene.GROUP, "G1")).enabled is False

    async def test_scenes_do_not_collide(self, database: Database) -> None:
        """群内标识与单聊标识分属不同命名空间，同值也是两个会话。"""

        async with database.session() as session:
            store = Store(session)
            group = await store.get_chat(Scene.GROUP, "SAME")
            c2c = await store.get_chat(Scene.C2C, "SAME")

            group.enabled = False

            assert c2c.enabled is True


class TestGetMember:
    async def test_creates_on_first_sight(self, database: Database) -> None:
        async with database.session() as session:
            store = Store(session)
            await store.get_chat(Scene.GROUP, "G1")
            member = await store.get_member(Scene.GROUP, "G1", "U1")

            assert member.nickname is None

    async def test_nickname_persists(self, database: Database) -> None:
        async with database.session() as session:
            store = Store(session)
            await store.get_chat(Scene.GROUP, "G1")
            (await store.get_member(Scene.GROUP, "G1", "U1")).nickname = "调查员"

        async with database.session() as session:
            member = await Store(session).get_member(Scene.GROUP, "G1", "U1")

            assert member.nickname == "调查员"

    async def test_members_are_scoped_to_their_chat(self, database: Database) -> None:
        """同一个人在不同群里是两条记录，昵称互不影响。"""

        async with database.session() as session:
            store = Store(session)
            await store.get_chat(Scene.GROUP, "G1")
            await store.get_chat(Scene.GROUP, "G2")
            (await store.get_member(Scene.GROUP, "G1", "U1")).nickname = "调查员"

            assert (await store.get_member(Scene.GROUP, "G2", "U1")).nickname is None

    async def test_lists_members_of_one_chat(self, database: Database) -> None:
        async with database.session() as session:
            store = Store(session)
            await store.get_chat(Scene.GROUP, "G1")
            await store.get_chat(Scene.GROUP, "G2")
            await store.get_member(Scene.GROUP, "G1", "U1")
            await store.get_member(Scene.GROUP, "G1", "U2")
            await store.get_member(Scene.GROUP, "G2", "U3")

            members = await store.list_members(Scene.GROUP, "G1")

            assert {member.openid for member in members} == {"U1", "U2"}


class TestSession:
    async def test_rolls_back_on_error(self, database: Database) -> None:
        async with database.session() as session:
            await Store(session).get_chat(Scene.GROUP, "G1")

        with suppress(RuntimeError):
            async with database.session() as session:
                (await Store(session).get_chat(Scene.GROUP, "G1")).enabled = False
                raise RuntimeError("boom")

        async with database.session() as session:
            assert (await Store(session).get_chat(Scene.GROUP, "G1")).enabled is True

    async def test_timestamps_are_generated_by_the_database(self, database: Database) -> None:
        async with database.session() as session:
            chat: Chat = await Store(session).get_chat(Scene.GROUP, "G1")

            assert chat.created_at is not None
            assert chat.updated_at is not None


class TestConcurrentCreation:
    """多个 worker 会同时遇到同一个新群：都查不到记录，于是都执行插入。"""

    async def test_a_conflicting_insert_falls_back_to_the_existing_row(
        self, database: Database, monkeypatch: pytest.MonkeyPatch
    ) -> None:
        """冲突的一方必须改取对方创建的记录，且本会话的其他改动不受影响。"""

        async with database.session() as session:
            # 另一个 worker 抢先创建并提交。
            async with database.session() as other:
                await Store(other).get_chat(Scene.GROUP, "G1")

            # 令本会话的首次查询落空，以此模拟它发生在对方提交之前——插入才会真正撞上主键冲突。
            original = session.get
            missed = False

            async def get_with_one_miss(*args: Any, **kwargs: Any) -> Any:
                nonlocal missed

                if not missed:
                    missed = True

                    return None

                return await original(*args, **kwargs)

            monkeypatch.setattr(session, "get", get_with_one_miss)

            chat = await Store(session).get_chat(Scene.GROUP, "G1")

            assert chat.openid == "G1"

            # SAVEPOINT 只回滚了那次插入，本会话仍可继续写入并提交。
            chat.enabled = False

        async with database.session() as session:
            assert (await Store(session).get_chat(Scene.GROUP, "G1")).enabled is False

    async def test_parallel_creation_leaves_one_row(self, database: Database) -> None:
        """并发创建同一条记录不应抛出异常，也不应留下重复记录。

        能否触发冲突取决于调度，上一例才是钉住回退分支的那个。
        """

        async def touch() -> Chat:
            async with database.session() as session:
                return await Store(session).get_chat(Scene.GROUP, "G1")

        chats = await asyncio.gather(*(touch() for _ in range(8)))

        assert [chat.openid for chat in chats] == ["G1"] * 8

        async with database.session() as session:
            assert await session.scalar(select(func.count()).select_from(Chat)) == 1


class TestPragmas:
    async def test_wal_is_enabled(self, database: Database) -> None:
        """默认的回滚日志下，一个 worker 的读事务会挡住其他 worker 的提交。"""

        async with database.engine.connect() as connection:
            assert await connection.scalar(text("PRAGMA journal_mode")) == "wal"

    async def test_busy_timeout_is_set(self, database: Database) -> None:
        async with database.engine.connect() as connection:
            assert await connection.scalar(text("PRAGMA busy_timeout")) == 5000


class TestPluginState:
    async def test_global_state_is_created_on_first_sight(self, database: Database) -> None:
        async with database.session() as session:
            state = await Store(session).get_plugin_state("dice")

            assert state.enabled is True
            assert state.settings == {}

    async def test_chat_state_is_scoped_to_the_plugin(self, database: Database) -> None:
        """插件设置互不干扰，这正是它取代 chats 表专属列的理由。"""

        async with database.session() as session:
            store = Store(session)
            await store.get_chat(Scene.GROUP, "G1")
            dice = await store.get_chat_plugin_state(Scene.GROUP, "G1", "dice")
            dice.settings = {"default_surface": 20}

            check = await store.get_chat_plugin_state(Scene.GROUP, "G1", "check")

            assert check.settings == {}

    async def test_chat_settings_persist(self, database: Database) -> None:
        async with database.session() as session:
            store = Store(session)
            await store.get_chat(Scene.GROUP, "G1")
            state = await store.get_chat_plugin_state(Scene.GROUP, "G1", "dice")
            state.settings = {"default_surface": 20}
            state.enabled = False

        async with database.session() as session:
            state = await Store(session).get_chat_plugin_state(Scene.GROUP, "G1", "dice")

            assert state.settings == {"default_surface": 20}
            assert state.enabled is False

    async def test_a_plugin_never_seen_before_gets_defaults(self, database: Database) -> None:
        """新装的插件没有存量记录，取用时应当直接拿到默认状态。"""

        async with database.session() as session:
            store = Store(session)
            await store.get_chat(Scene.GROUP, "G1")
            state = await store.get_chat_plugin_state(Scene.GROUP, "G1", "brand_new")

            assert state.enabled is True
            assert state.settings == {}
