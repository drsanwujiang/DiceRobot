"""执行上下文。

插件实现仅接触这些对象：从中读取来源、参数与设置，输出统一经 :meth:`write` 提交，
无需关心当前场景或剩余回复配额。

设置以 JSON 存储，读取时由插件声明的 pydantic 模型校验并补齐默认值，因此插件增删
设置项不需要迁移数据库。写回必须显式调用 ``save_*``：读远多于写，脏检测要么依赖
模型的变更追踪，要么每次序列化比对，都不划算。
"""

from __future__ import annotations

from pydantic import BaseModel

from dicerobot.bot.message import IncomingEvent, IncomingMessage
from dicerobot.bot.outbound import ReplyBuffer
from dicerobot.storage import Chat, ChatPluginState, Member, PluginState, Store

__all__ = ["CommandContext", "Context", "EventContext"]

_ANONYMOUS_ID_LENGTH = 4


class Context:
    """指令与事件共有的上下文。"""

    def __init__(
        self,
        *,
        buffer: ReplyBuffer,
        chat: Chat,
        plugin_state: PluginState,
        chat_plugin_state: ChatPluginState,
        store: Store,
    ) -> None:
        """
        Args:
            buffer: 输出缓冲。
            chat: 当前会话，直接赋值即生效，提交由调度器完成。
            plugin_state: 所属插件的全局状态。
            chat_plugin_state: 所属插件在当前会话中的状态。
            store: 数据访问入口，用于本次调用需要读写的其他记录。
        """

        self.chat = chat
        self.store = store

        self._buffer = buffer
        self._plugin_state = plugin_state
        self._chat_plugin_state = chat_plugin_state

    def settings[T: BaseModel](self, model: type[T]) -> T:
        """读取插件的全局设置。"""

        return model.model_validate(self._plugin_state.settings)

    def save_settings(self, settings: BaseModel) -> None:
        """写回插件的全局设置。"""

        self._plugin_state.settings = settings.model_dump(mode="json")

    def chat_settings[T: BaseModel](self, model: type[T]) -> T:
        """读取插件在当前会话中的设置。"""

        return model.model_validate(self._chat_plugin_state.settings)

    def save_chat_settings(self, settings: BaseModel) -> None:
        """写回插件在当前会话中的设置。"""

        self._chat_plugin_state.settings = settings.model_dump(mode="json")

    def write(self, text: str) -> None:
        """追加一段输出。执行结束后由调度器合并发送，多次调用只消耗一条回复配额。"""

        self._buffer.write(text)

    async def flush(self) -> bool:
        """立即发出已累积的输出。

        用于长耗时处理的进度提示。每次调用消耗一条回复配额。
        """

        return await self._buffer.flush()


class CommandContext(Context):
    """一次指令调用的上下文。"""

    def __init__(
        self,
        *,
        message: IncomingMessage,
        name: str,
        args: str,
        times: int,
        buffer: ReplyBuffer,
        chat: Chat,
        member: Member,
        plugin_state: PluginState,
        chat_plugin_state: ChatPluginState,
        store: Store,
    ) -> None:
        """
        Args:
            message: 触发本次调用的消息。
            name: 实际匹配到的指令名，可用于区分同一插件的多个入口。
            args: 指令名之后的参数原文，已去除首尾空白。
            times: ``#N`` 指定的重复次数，未指定时为 1。
            member: 当前发送者的记录。

        其余参数含义见 :class:`Context`。
        """

        super().__init__(
            buffer=buffer,
            chat=chat,
            plugin_state=plugin_state,
            chat_plugin_state=chat_plugin_state,
            store=store,
        )

        self.message = message
        self.name = name
        self.args = args
        self.times = times
        self.member = member

    @property
    def display_name(self) -> str:
        """发送者的可读名称。

        平台不提供昵称，未通过 ``.nn`` 设置时退回 openid 的末几位，以便在同一会话中
        区分不同的人。
        """

        if self.member.nickname:
            return self.member.nickname

        return f"玩家{self.member.openid[-_ANONYMOUS_ID_LENGTH:].upper()}"


class EventContext(Context):
    """一次事件处理的上下文。

    事件不来自某个具体成员，因此没有 ``member`` 与 ``display_name``；触发者的标识
    在 ``event.operator_id`` 中，平台未提供时为空。
    """

    def __init__(
        self,
        *,
        event: IncomingEvent,
        buffer: ReplyBuffer,
        chat: Chat,
        plugin_state: PluginState,
        chat_plugin_state: ChatPluginState,
        store: Store,
    ) -> None:
        """
        Args:
            event: 触发本次处理的事件。

        其余参数含义见 :class:`Context`。
        """

        super().__init__(
            buffer=buffer,
            chat=chat,
            plugin_state=plugin_state,
            chat_plugin_state=chat_plugin_state,
            store=store,
        )

        self.event = event
