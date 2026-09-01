"""数据访问。

会话与成员在首次出现时创建：平台不提供成员列表，也没有入群通知之外的注册时机，
记录只能由消息本身触发。

多个 worker 可能同时处理同一个群的消息，因而会同时创建同一条记录，见
:meth:`Store._get_or_create`。
"""

from __future__ import annotations

from typing import Any

from sqlalchemy import select
from sqlalchemy.exc import IntegrityError
from sqlalchemy.ext.asyncio import AsyncSession

from dicerobot.enums import Scene
from dicerobot.storage.models import Base, Chat, ChatPluginState, Member, PluginState

__all__ = ["Store"]


class Store:
    """在一个数据库会话上提供的数据访问入口。

    返回的对象由该会话托管，直接赋值即可，提交由 :class:`~dicerobot.storage.database.Database`
    在会话结束时完成。
    """

    def __init__(self, session: AsyncSession) -> None:
        self._session = session

    async def get_chat(self, scene: Scene, openid: str) -> Chat:
        """取出会话记录，不存在则创建。"""

        return await self._get_or_create(Chat, (scene, openid), scene=scene, openid=openid)

    async def get_member(self, scene: Scene, chat_openid: str, openid: str) -> Member:
        """取出成员记录，不存在则创建。"""

        return await self._get_or_create(
            Member, (scene, chat_openid, openid), scene=scene, chat_openid=chat_openid, openid=openid
        )

    async def get_plugin_state(self, plugin: str) -> PluginState:
        """取出插件的全局状态，不存在则创建。"""

        return await self._get_or_create(PluginState, plugin, plugin=plugin, settings={})

    async def get_chat_plugin_state(self, scene: Scene, chat_openid: str, plugin: str) -> ChatPluginState:
        """取出插件在某个会话中的状态，不存在则创建。"""

        return await self._get_or_create(
            ChatPluginState,
            (scene, chat_openid, plugin),
            scene=scene,
            chat_openid=chat_openid,
            plugin=plugin,
            settings={},
        )

    async def _get_or_create[T: Base](self, model: type[T], key: Any, **values: Any) -> T:
        """取出记录，不存在则创建。

        并发的 worker 会同时遇到同一个新会话或新成员，两侧都查不到记录，于是都执行插入，
        后提交的一方撞上主键冲突。插入放在 SAVEPOINT 中，冲突后只回滚这一步并改取对方
        创建的记录，指令在同一会话中已产生的其他改动不受影响。
        """

        instance = await self._session.get(model, key)

        if instance is not None:
            return instance

        try:
            async with self._session.begin_nested():
                instance = model(**values)
                self._session.add(instance)
                # 立即写入，使随后创建的记录能满足外键约束，冲突也在此暴露。
                await self._session.flush()
        except IntegrityError:
            instance = await self._session.get(model, key)

            if instance is None:
                raise

        return instance

    async def list_members(self, scene: Scene, chat_openid: str) -> list[Member]:
        """列出某个会话中已有记录的成员。"""

        result = await self._session.scalars(
            select(Member).where(Member.scene == scene, Member.chat_openid == chat_openid)
        )

        return list(result)
