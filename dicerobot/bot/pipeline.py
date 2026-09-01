"""事件处理流水线。

Webhook 须立即返回，平台在超时未收到响应时会重推同一事件，因此实际处理交由后台
worker 完成。

群若被开启全量消息推送，每条群消息都会到达，故入队后的快速路径开销要低：先按前缀
与指令表判断是否命中，未命中的消息在进入下游之前丢弃。
"""

from __future__ import annotations

import asyncio
import time
from collections.abc import Callable
from datetime import UTC, datetime

from loguru import logger

from dicerobot.bot.context import CommandContext, EventContext
from dicerobot.bot.dedup import EventDeduplicator
from dicerobot.bot.message import IncomingEvent, IncomingMessage, normalize_event, normalize_message
from dicerobot.bot.outbound import DirectSession, ReplyBuffer, ReplySession
from dicerobot.bot.plugin import EventHandler as EventHandlerType
from dicerobot.bot.plugin import Plugin
from dicerobot.bot.registry import Invocation, Registry
from dicerobot.config import BotSettings
from dicerobot.enums import Scene
from dicerobot.errors import ApiError, CommandError, ReplyError
from dicerobot.qq.client import QQClient
from dicerobot.qq.schemas import Payload
from dicerobot.storage import Chat, ChatPluginState, Database, PluginState, Store

__all__ = ["Pipeline"]

_FAILURE_REPLY = "指令执行出错了，请稍后再试……"
_TIMEOUT_REPLY = "指令执行超时了……"
_PRIVATE_FAILURE_REPLY = "私聊消息发送失败，请确认没有在客户端关闭机器人的主动消息……"

_CONTENT_PREVIEW = 50
"""正文在日志中截断至此长度，群开启全量推送时每条消息都会记录一行。"""


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

        # 本方法自行绑定事件 ID，不依赖调用方的上下文。
        with logger.contextualize(event_id=payload.id):
            if payload.id is not None and not self._deduplicator.is_new(payload.id):
                logger.debug("事件重复推送，已丢弃")
                return

            try:
                self._queue.put_nowait((payload, self._now()))
            except asyncio.QueueFull:
                logger.warning("事件队列已满（容量 {}），丢弃事件", self._settings.queue_size)
            else:
                logger.debug("事件入队，队列深度 {}/{}", self._queue.qsize(), self._settings.queue_size)

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

            # 事件 ID 绑定到日志上下文，处理期间产生的日志（含插件与平台调用）都会携带
            # 该字段。上下文由 contextvar 承载，各 worker 为独立任务，互不影响。
            with logger.contextualize(event_id=payload.id):
                # 排队耗时跨 submit 与 worker 两个调用点，只能使用注入的时钟；处理耗时在
                # 同一处取值，改用单调时钟，避免系统时间调整导致负值。
                queue_wait_ms = (self._now() - received_at).total_seconds() * 1000
                started = time.perf_counter()

                logger.debug("开始处理事件，排队耗时 {:.1f} ms", queue_wait_ms)

                try:
                    await self._process(payload, received_at)
                except Exception:
                    # worker 须存活至关停，任何未捕获的异常都不能使其退出。
                    logger.exception("处理事件时发生未捕获的异常")
                finally:
                    # 异常路径同样记录：处理失败的事件往往耗时最长，缺少这行便无从排查。
                    logger.debug("事件处理完成，耗时 {:.1f} ms", (time.perf_counter() - started) * 1000)
                    self._queue.task_done()

    async def _process(self, payload: Payload, received_at: datetime) -> None:
        message = normalize_message(payload, received_at=received_at)

        if message is None:
            await self._process_event(payload, received_at)
            return

        # 单聊没有群，其 scene_id 即发送者本人，不再重复记录。
        group = f"，group={message.scene_id}" if message.scene is Scene.GROUP else ""

        logger.debug(
            "收到消息 {}：timestamp={}，sender={}{}，content={!r}",
            message.message_id,
            message.timestamp or "-",
            message.sender_id,
            group,
            message.content[:_CONTENT_PREVIEW],
        )

        if message.addressed_to_others:
            logger.debug("消息未 @ 到自己，已丢弃")
            return

        invocation = self._registry.resolve(message.content)

        if invocation is None:
            logger.debug("消息未匹配任何指令，已丢弃")
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
        private = ReplyBuffer(DirectSession(client=self._client, openid=message.sender_id))

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
                private=private,
                chat=chat,
                member=member,
                plugin_state=plugin_state,
                chat_plugin_state=chat_plugin_state,
                store=store,
            )

            await self._run(context, invocation, buffer)

        # 会话提交之后再发送：一次平台调用约 700 ms，横跨事务会让其他 worker 的提交一直
        # 等在 SQLite 的写锁上。发送失败原本也不回滚，改变先后顺序不影响既有语义。
        #
        # 私聊排在前面，其失败才来得及在原会话的回复里提示。
        await self._deliver(private, buffer)
        await self._reply(buffer)

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

        await self._reply(buffer)

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
            # 事件不是用户主动发起的，失败不回复错误提示，以免在入群等场景下连续发送无效回复。
            # 单个插件出错也不应影响其余插件。
            logger.exception("插件 {} 处理事件 {} 失败", plugin.name, event.type)

            if self._debug:
                raise

    @staticmethod
    def _is_enabled(chat: Chat, plugin_state: PluginState, chat_plugin_state: ChatPluginState) -> bool:
        """三层开关：会话级总开关、插件全局开关、插件在本会话的开关。"""

        return chat.enabled and plugin_state.enabled and chat_plugin_state.enabled

    async def _run(self, context: CommandContext, invocation: Invocation, buffer: ReplyBuffer) -> None:
        """执行 handler。输出留在缓冲中，由调用方在会话结束后发出。"""

        try:
            if invocation.times > invocation.command.max_times:
                raise CommandError(f"这个指令最多只能重复 {invocation.command.max_times} 次……")

            async with asyncio.timeout(self._settings.handler_timeout):
                await invocation.command.handler(context)
        except CommandError as e:
            # 用户输入有误，回复原因并丢弃指令已产生的部分输出。
            buffer.clear()
            buffer.write(e.message)
        except TimeoutError:
            logger.warning("指令 {} 执行超时（{} 秒）", invocation.name, self._settings.handler_timeout)
            buffer.clear()
            buffer.write(_TIMEOUT_REPLY)
        except ReplyError as e:
            # 指令自行调用 flush 发送进度提示时失败：回复通道不可用，缓冲中的内容同样发不出去。
            logger.warning("指令 {} 的回复无法送达：{}", invocation.name, e)
            buffer.clear()
        except Exception:
            logger.exception("指令 {} 执行失败", invocation.name)

            if self._debug:
                raise

            buffer.clear()
            buffer.write(_FAILURE_REPLY)

    @staticmethod
    async def _deliver(private: ReplyBuffer, buffer: ReplyBuffer) -> None:
        """发出私聊输出，失败则在原会话的回复中追加提示。

        用户可在客户端关闭主动消息，投递失败属于正常情形；提示措辞面向用户，故由此处
        统一给出，插件不必各自处理。
        """

        if not private.pending:
            return

        try:
            await private.flush()
        except ApiError as e:
            logger.warning("私聊消息发送失败：{}", e)
            buffer.write(_PRIVATE_FAILURE_REPLY)

    @staticmethod
    async def _reply(buffer: ReplyBuffer) -> None:
        """发出缓冲中的内容，失败则仅记录日志。

        回复通道本身不可用时，再次回复只会重复失败。
        """

        try:
            await buffer.flush()
        except ReplyError as e:
            logger.warning("回复无法送达：{}", e)
