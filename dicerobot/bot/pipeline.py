"""事件处理流水线。

Webhook 须立即返回，平台在超时未收到响应时会重推同一事件，因此实际处理交由后台
worker 完成。

群若被开启全量消息推送，每条群消息都会到达，故入队后的快速路径需保持廉价：先按
前缀与指令表判断是否命中，未命中的消息在触及下游之前丢弃。
"""

from __future__ import annotations

import asyncio
from collections.abc import Callable
from datetime import UTC, datetime

from loguru import logger

from dicerobot.bot.context import CommandContext, EventContext
from dicerobot.bot.dedup import EventDeduplicator
from dicerobot.bot.message import IncomingEvent, IncomingMessage, normalize_event, normalize_message
from dicerobot.bot.outbound import ReplyBuffer, ReplySession
from dicerobot.bot.plugin import EventHandler as EventHandlerType
from dicerobot.bot.plugin import Plugin
from dicerobot.bot.registry import Invocation, Registry
from dicerobot.config import BotSettings
from dicerobot.errors import CommandError, ReplyError
from dicerobot.qq.client import QQClient
from dicerobot.qq.schemas import Payload
from dicerobot.storage import Chat, ChatPluginState, Database, PluginState, Store

__all__ = ["Pipeline"]

_FAILURE_REPLY = "指令执行出错了，请稍后再试……"
_TIMEOUT_REPLY = "指令执行超时了……"

_CONTENT_PREVIEW = 50
"""未命中指令的消息在日志中截断至此长度，群开启全量推送时这条日志会很密集。"""


class Pipeline:
    """接收事件并调度指令执行。"""

    def __init__(
        self,
        *,
        registry: Registry,
        client: QQClient,
        database: Database,
        settings: BotSettings,
        deduplicator: EventDeduplicator | None = None,
        debug: bool = False,
        now: Callable[[], datetime] = lambda: datetime.now(UTC),
    ) -> None:
        self._registry = registry
        self._client = client
        self._database = database
        self._settings = settings
        self._deduplicator = deduplicator if deduplicator is not None else EventDeduplicator(now=now)
        self._debug = debug
        self._now = now

        self._queue: asyncio.Queue[tuple[Payload, datetime]] = asyncio.Queue(maxsize=settings.queue_size)
        self._workers: list[asyncio.Task[None]] = []

    def submit(self, payload: Payload) -> None:
        """把事件交给流水线。

        调用方是 webhook 处理函数，故本方法不得阻塞。队列满时丢弃并告警而非反压，
        积压的事件多半已超出回复窗口。重推的事件在此被去重拦下。
        """

        if payload.id is not None and not self._deduplicator.is_new(payload.id):
            logger.debug("事件 {} 重复推送，已丢弃", payload.id)
            return

        try:
            self._queue.put_nowait((payload, self._now()))
        except asyncio.QueueFull:
            logger.warning("事件队列已满（容量 {}），丢弃事件 {}", self._settings.queue_size, payload.id)
        else:
            logger.debug("事件 {} 入队，队列深度 {}/{}", payload.id, self._queue.qsize(), self._settings.queue_size)

    async def start(self) -> None:
        """启动 worker。"""

        if self._workers:
            raise RuntimeError("流水线已经启动")

        self._workers = [
            asyncio.create_task(self._run_worker(), name=f"pipeline-worker-{index}")
            for index in range(self._settings.workers)
        ]

        logger.info("流水线已启动，{} 个 worker", self._settings.workers)

    async def stop(self, *, drain_timeout: float = 5.0) -> None:
        """停止 worker。

        先给积压事件一个有限的处理窗口，超时则直接取消以免阻塞关停。
        """

        if not self._workers:
            return

        try:
            await asyncio.wait_for(self._queue.join(), timeout=drain_timeout)
        except TimeoutError:
            logger.warning("关停时仍有 {} 个事件未处理，不再等待", self._queue.qsize())

        for worker in self._workers:
            worker.cancel()

        await asyncio.gather(*self._workers, return_exceptions=True)
        self._workers = []

        logger.info("流水线已停止")

    async def _run_worker(self) -> None:
        while True:
            payload, received_at = await self._queue.get()

            try:
                await self._process(payload, received_at)
            except Exception:
                # worker 须存活至关停，任何未捕获的异常都不能使其退出。
                logger.exception("处理事件 {} 时发生未捕获的异常", payload.id)
            finally:
                self._queue.task_done()

    async def _process(self, payload: Payload, received_at: datetime) -> None:
        message = normalize_message(payload, received_at=received_at)

        if message is None:
            await self._process_event(payload, received_at)
            return

        invocation = self._registry.resolve(message.content)

        if invocation is None:
            logger.debug(
                "消息未匹配任何指令：scene={}，sender={}，content={!r}",
                message.scene,
                message.sender_id,
                message.content[:_CONTENT_PREVIEW],
            )
            return

        logger.info(
            "{} {} 触发指令 {}（args={!r}，times={}）",
            message.scene,
            message.sender_id,
            invocation.name,
            invocation.args,
            invocation.times,
        )

        await self._execute(message, invocation)

    async def _execute(self, message: IncomingMessage, invocation: Invocation) -> None:
        buffer = ReplyBuffer(ReplySession(client=self._client, target=message.reply_target, now=self._now))

        # 会话在指令执行期间保持开启，指令对 chat 与 member 的修改在退出时一并提交。
        async with self._database.session() as session:
            store = Store(session)
            chat = await store.get_chat(message.scene, message.scene_id)
            plugin_state = await store.get_plugin_state(invocation.plugin.name)
            chat_plugin_state = await store.get_chat_plugin_state(
                message.scene, message.scene_id, invocation.plugin.name
            )

            if invocation.command.requires_enabled and not self._is_enabled(chat, plugin_state, chat_plugin_state):
                logger.debug("指令 {} 在 {} 中被停用，跳过", invocation.name, message.scene_id)
                return

            member = await store.get_member(message.scene, message.scene_id, message.sender_id)
            context = CommandContext(
                message=message,
                name=invocation.name,
                args=invocation.args,
                times=invocation.times,
                buffer=buffer,
                chat=chat,
                member=member,
                plugin_state=plugin_state,
                chat_plugin_state=chat_plugin_state,
                store=store,
            )

            await self._run(context, invocation, buffer)

    async def _process_event(self, payload: Payload, received_at: datetime) -> None:
        """派发非消息事件。

        多个插件可以响应同一事件，它们共用一个回复会话与缓冲，输出合并为一条消息，
        只消耗一条配额。
        """

        event = normalize_event(payload, received_at=received_at)

        if event is None:
            return

        handlers = self._registry.event_handlers(event.type)

        if not handlers:
            logger.debug("事件 {} 没有对应的处理器，跳过", event.type)
            return

        logger.info("{} {} 触发事件 {}", event.scene, event.scene_id, event.type)
        buffer = ReplyBuffer(ReplySession(client=self._client, target=event.reply_target, now=self._now))

        async with self._database.session() as session:
            store = Store(session)
            chat = await store.get_chat(event.scene, event.scene_id)

            for plugin, spec in handlers:
                await self._run_event_handler(plugin, spec.handler, event, chat, store, buffer)

        await self._reply_quietly(buffer)

    async def _run_event_handler(
        self,
        plugin: Plugin,
        handler: EventHandlerType,
        event: IncomingEvent,
        chat: Chat,
        store: Store,
        buffer: ReplyBuffer,
    ) -> None:
        plugin_state = await store.get_plugin_state(plugin.name)
        chat_plugin_state = await store.get_chat_plugin_state(event.scene, event.scene_id, plugin.name)

        if not self._is_enabled(chat, plugin_state, chat_plugin_state):
            return

        context = EventContext(
            event=event,
            buffer=buffer,
            chat=chat,
            plugin_state=plugin_state,
            chat_plugin_state=chat_plugin_state,
            store=store,
        )

        try:
            async with asyncio.timeout(self._settings.handler_timeout):
                await handler(context)
        except Exception:
            # 事件不是用户主动发起的，失败不回复错误提示，以免在入群一类的时刻刷屏。
            # 单个插件出错也不应影响其余插件。
            logger.exception("插件 {} 处理事件 {} 失败", plugin.name, event.type)

            if self._debug:
                raise

    @staticmethod
    def _is_enabled(chat: Chat, plugin_state: PluginState, chat_plugin_state: ChatPluginState) -> bool:
        """三层开关：会话级总开关、插件全局开关、插件在本会话的开关。"""

        return chat.enabled and plugin_state.enabled and chat_plugin_state.enabled

    async def _run(self, context: CommandContext, invocation: Invocation, buffer: ReplyBuffer) -> None:
        try:
            if invocation.times > invocation.command.max_times:
                raise CommandError(f"这个指令最多只能重复 {invocation.command.max_times} 次……")

            async with asyncio.timeout(self._settings.handler_timeout):
                await invocation.command.handler(context)

            await buffer.flush()
        except CommandError as e:
            # 用户输入有误，回复原因并丢弃指令已产生的部分输出。
            buffer.clear()
            buffer.write(e.message)
            await self._reply_quietly(buffer)
        except TimeoutError:
            logger.warning("指令 {} 执行超时（{} 秒）", invocation.name, self._settings.handler_timeout)
            buffer.clear()
            buffer.write(_TIMEOUT_REPLY)
            await self._reply_quietly(buffer)
        except ReplyError as e:
            # 回复通道本身不可用，再次回复只会重复失败。
            logger.warning("指令 {} 的回复无法送达：{}", invocation.name, e)
        except Exception:
            logger.exception("指令 {} 执行失败", invocation.name)

            if self._debug:
                raise

            buffer.clear()
            buffer.write(_FAILURE_REPLY)
            await self._reply_quietly(buffer)

    @staticmethod
    async def _reply_quietly(buffer: ReplyBuffer) -> None:
        """尽力发出错误提示，失败则仅记录日志。"""

        try:
            await buffer.flush()
        except ReplyError as e:
            logger.warning("错误提示无法送达：{}", e)
