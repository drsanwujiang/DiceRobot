"""数据访问。

会话与成员在首次出现时创建：平台不提供成员列表，也没有入群通知之外的注册时机，
记录只能由消息本身触发。
"""

from __future__ import annotations

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from dicerobot.enums import Scene
from dicerobot.storage.models import Chat, ChatPluginState, Member, PluginState

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

        chat = await self._session.get(Chat, (scene, openid))

        if chat is None:
            chat = Chat(scene=scene, openid=openid)
            self._session.add(chat)
            # 立即写入，使随后创建的成员记录能满足外键约束。
            await self._session.flush()

        return chat

    async def get_member(self, scene: Scene, chat_openid: str, openid: str) -> Member:
        """取出成员记录，不存在则创建。"""

        member = await self._session.get(Member, (scene, chat_openid, openid))

        if member is None:
            member = Member(scene=scene, chat_openid=chat_openid, openid=openid)
            self._session.add(member)
            await self._session.flush()

        return member

    async def get_plugin_state(self, plugin: str) -> PluginState:
        """取出插件的全局状态，不存在则创建。"""

        state = await self._session.get(PluginState, plugin)

        if state is None:
            state = PluginState(plugin=plugin, settings={})
            self._session.add(state)
            await self._session.flush()

        return state

    async def get_chat_plugin_state(self, scene: Scene, chat_openid: str, plugin: str) -> ChatPluginState:
        """取出插件在某个会话中的状态，不存在则创建。"""

        state = await self._session.get(ChatPluginState, (scene, chat_openid, plugin))

        if state is None:
            state = ChatPluginState(scene=scene, chat_openid=chat_openid, plugin=plugin, settings={})
            self._session.add(state)
            await self._session.flush()

        return state

    async def list_members(self, scene: Scene, chat_openid: str) -> list[Member]:
        """列出某个会话中已有记录的成员。"""

        result = await self._session.scalars(
            select(Member).where(Member.scene == scene, Member.chat_openid == chat_openid)
        )

        return list(result)
