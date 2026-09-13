"""WebSocket 网关客户端。

平台的两种事件接收方式互斥，在开放平台管理端切换：配置了回调地址即 webhook，否则由本方
主动建立长连接。WebSocket 不需要公网域名、HTTPS 证书与固定 IP，代价是会话须自行维护
——取接入点、鉴权、心跳、断线重连与事件续传都在本模块。

事件经与 webhook 相同的 ``EventSink`` 交给运行时，因此下游对事件由哪一方送达并不知情。
"""

from __future__ import annotations

import asyncio
import json
import time
from typing import Any

from loguru import logger
from pydantic import ValidationError
from websockets.asyncio.client import ClientConnection, connect
from websockets.exceptions import ConnectionClosed, ConnectionClosedOK, WebSocketException
from websockets.typing import Data

from dicerobot.errors import GatewayError, GatewayRejectedError, QQError
from dicerobot.logging import preview
from dicerobot.qq import EventSink
from dicerobot.qq.client import QQClient
from dicerobot.qq.enums import CloseCode, OpCode
from dicerobot.qq.schemas import HelloData, Payload, ReadyData
from dicerobot.qq.token import AccessTokenProvider

__all__ = ["GatewayClient"]

# 订阅的事件位。群聊与单聊的全部事件都在这一位之内：群内 @ 消息、全量群消息、单聊消息、
# 机器人进出群与好友增删。频道相关的位另需对应的机器人类型与权限，声明了未获授权的位
# 平台会以关闭码 4014 拒绝连接，故不一并请求。
_INTENTS = 1 << 25

# 分片。单实例部署不分片，事件全部落在这一片上。
_SHARD = [0, 1]

# 描述连接自身状态的事件类型，就地消化，不投给运行时。
_READY = "READY"
_RESUMED = "RESUMED"

# 重连退避：首次等待 _INITIAL_RECONNECT_DELAY 秒，其后逐次加倍至上限。
_INITIAL_RECONNECT_DELAY = 1.0
_MAX_RECONNECT_DELAY = 60.0

# 判定连接已死的静默时长，以心跳间隔的倍数计。
_SILENCE_INTERVALS = 2

# 重连也不会恢复的关闭码：鉴权参数非法，或机器人已下架、封禁。
_FATAL_CLOSE_CODES = frozenset(
    {
        CloseCode.INVALID_SHARD,
        CloseCode.SHARDING_REQUIRED,
        CloseCode.INVALID_VERSION,
        CloseCode.INVALID_INTENTS,
        CloseCode.INTENTS_NOT_AUTHORIZED,
        CloseCode.BOT_OFFLINE,
        CloseCode.BOT_BANNED,
    }
)

# 会话不可续传的关闭码：重连后须重新鉴权而非恢复。
_SESSION_INVALID_CLOSE_CODES = frozenset({CloseCode.INVALID_SESSION, CloseCode.INVALID_SEQ})


class GatewayClient:
    """维护与平台网关的长连接，把下发的事件交给 sink。

    一条后台任务承载连接的全部生命周期，调用方只需 :meth:`start` 与 :meth:`stop`。
    """

    def __init__(
        self,
        *,
        client: QQClient,
        token_provider: AccessTokenProvider,
        sink: EventSink,
        initial_reconnect_delay: float = _INITIAL_RECONNECT_DELAY,
        max_reconnect_delay: float = _MAX_RECONNECT_DELAY,
    ) -> None:
        """
        Args:
            client: OpenAPI 客户端，用于取 WSS 接入点。
            token_provider: access token 来源，鉴权与恢复会话都需要。
            sink: 事件接收方。
            initial_reconnect_delay: 首次重连前的等待时长。
            max_reconnect_delay: 重连等待时长的上限。
        """

        self._client = client
        self._token_provider = token_provider
        self._sink = sink
        self._initial_reconnect_delay = initial_reconnect_delay
        self._max_reconnect_delay = max_reconnect_delay

        self._task: asyncio.Task[None] | None = None
        self._session_id: str | None = None
        self._last_seq: int | None = None
        self._authenticated = False
        self._last_received = 0.0

    async def start(self) -> None:
        """开始接收事件。

        连接在后台任务中建立，本方法不等待其就绪：平台或网络异常时不应阻断启动，重连
        由任务自身负责。

        Raises:
            RuntimeError: 重复启动。
        """

        if self._task is not None:
            raise RuntimeError("网关客户端已经启动")

        self._task = asyncio.create_task(self._run(), name="gateway")

        logger.debug("网关客户端已启动")

    async def stop(self) -> None:
        """断开连接。未启动时为空操作。"""

        if self._task is None:
            return

        self._task.cancel()
        await asyncio.gather(self._task, return_exceptions=True)
        self._task = None

        logger.info("网关连接已断开")

    async def _run(self) -> None:
        """连接、断开、重连的主循环。"""

        delay = self._initial_reconnect_delay

        while True:
            self._authenticated = False

            try:
                await self._connect()
            except GatewayRejectedError as e:
                # 重连不会恢复，继续尝试只是徒然消耗连接配额。机器人自此不再接收事件，
                # 故以最高级别记录。
                logger.critical("网关拒绝连接，已停止接收事件：{}", e)

                return
            except (QQError, WebSocketException, OSError, TimeoutError) as e:
                logger.warning("网关连接中断：{}", e)
            except Exception:
                # 任务须存活至关停：未预料的异常同样只影响本次连接。
                logger.exception("网关连接发生未捕获的异常")

            # 活到鉴权完成的连接视为健康，退避重新起算，偶发断线因此不必为先前的失败等待；
            # 始终等待一次则保证平台持续拒绝时不会密集重试。
            if self._authenticated:
                delay = self._initial_reconnect_delay

            logger.info("{:.0f} 秒后重连网关", delay)

            await asyncio.sleep(delay)

            delay = min(delay * 2, self._max_reconnect_delay)

    async def _connect(self) -> None:
        """走完一次连接的生命周期。正常返回即代表连接已断，可以重连。"""

        gateway = await self._client.get_gateway()

        logger.debug("正在连接网关 {}", gateway.url)

        # ws 层的保活 ping 关闭：保活由平台的心跳（op 1 / op 11）负责，而平台是否应答 ws
        # ping 并无文档承诺，若不应答则每过一个 ping_timeout 就会误判一次断线。半开连接
        # 改由下行帧的静默时长判定，见 :meth:`_heartbeat`。
        async with connect(gateway.url, ping_interval=None) as connection:
            self._last_received = time.monotonic()
            heartbeat: asyncio.Task[None] | None = None

            try:
                interval = await self._hello(connection)
                await self._authenticate(connection)

                # 首个心跳在一个间隔之后才发出，届时鉴权早已完成，不必另等 READY。
                heartbeat = asyncio.create_task(self._heartbeat(connection, interval), name="gateway-heartbeat")

                await self._receive(connection)
            except ConnectionClosed as e:
                self._on_closed(e)
            finally:
                if heartbeat is not None:
                    heartbeat.cancel()
                    await asyncio.gather(heartbeat, return_exceptions=True)

    async def _hello(self, connection: ClientConnection) -> float:
        """收下 Hello，返回心跳间隔（秒）。

        Raises:
            GatewayError: 首帧不是 Hello，或其数据无法解析。
        """

        payload = self._parse(await connection.recv())

        if payload is None or payload.op != OpCode.HELLO:
            raise GatewayError("连接建立后未收到 Hello")

        try:
            hello = HelloData.model_validate(payload.d)
        except ValidationError as e:
            raise GatewayError(f"Hello 的数据无法解析：{e}") from e

        logger.debug("网关心跳间隔 {} 毫秒", hello.heartbeat_interval)

        return hello.heartbeat_interval / 1000

    async def _authenticate(self, connection: ClientConnection) -> None:
        """鉴权，或恢复已有会话。

        有会话可续时优先恢复：平台随后补发断连期间遗漏的事件，重新鉴权则会丢掉它们。
        """

        # 凭据与 OpenAPI 的 Authorization 头同形。
        token = f"QQBot {await self._token_provider.get()}"

        if self._session_id is not None and self._last_seq is not None:
            logger.info("正在恢复网关会话，session_id={}，seq={}", self._session_id, self._last_seq)

            await self._send(
                connection,
                {
                    "op": OpCode.RESUME.value,
                    "d": {"token": token, "session_id": self._session_id, "seq": self._last_seq},
                },
            )

            return

        logger.info("正在向网关鉴权，intents={}", _INTENTS)

        await self._send(
            connection,
            {
                "op": OpCode.IDENTIFY.value,
                "d": {"token": token, "intents": _INTENTS, "shard": _SHARD, "properties": {}},
            },
        )

    async def _receive(self, connection: ClientConnection) -> None:
        """接收下行帧，直至连接断开或平台要求重连。"""

        async for frame in connection:
            self._last_received = time.monotonic()
            payload = self._parse(frame)

            if payload is None:
                continue

            # seq 只随事件递增，恢复会话时须回传最后收到的取值。
            if payload.s is not None:
                self._last_seq = payload.s

            match payload.op:
                case OpCode.DISPATCH:
                    self._dispatch(payload)
                case OpCode.HEARTBEAT_ACK:
                    logger.trace("收到心跳应答")
                case OpCode.RECONNECT:
                    # 会话仍然有效，重连后恢复即可。
                    logger.info("平台要求重连")

                    return
                case OpCode.INVALID_SESSION:
                    logger.warning("网关会话已失效，重连后将重新鉴权")
                    self._forget_session()

                    return
                case _:
                    logger.debug("收到未处理的操作码 {}", payload.op)

    async def _heartbeat(self, connection: ClientConnection, interval: float) -> None:
        """按平台指定的间隔发送心跳，并据下行帧的静默时长判定连接是否已死。

        ws 层的保活 ping 已关闭，半开连接只能由本方发现：对端静默时 TCP 不会立即报错，
        不主动断开会让机器人失聪到内核放弃重传为止，那是十几分钟。心跳应答本身即是下行
        帧，故连续两个间隔没有收到任何帧即视为连接已死。
        """

        try:
            while True:
                await asyncio.sleep(interval)

                silence = time.monotonic() - self._last_received

                if silence > interval * _SILENCE_INTERVALS:
                    logger.warning("网关已静默 {:.1f} 秒，主动断开并重连", silence)
                    await connection.close()

                    return

                await self._send(connection, {"op": OpCode.HEARTBEAT.value, "d": self._last_seq})

                logger.trace("已发送心跳，seq={}", self._last_seq)
        except ConnectionClosed:
            # 连接已断，交由接收循环处置。
            pass
        except Exception:
            logger.exception("发送网关心跳时发生未捕获的异常")

    def _dispatch(self, payload: Payload) -> None:
        """处置一个事件帧。"""

        if payload.t == _READY:
            self._on_ready(payload)

            return

        if payload.t == _RESUMED:
            self._authenticated = True

            logger.info("网关会话已恢复，平台将补发 seq={} 之后遗漏的事件", self._last_seq)

            return

        # 事件 ID 自此进入日志上下文，与后续处理阶段的日志关联。
        with logger.contextualize(event_id=payload.id):
            logger.debug("收到事件下发：t={}", payload.t)

            self._sink.submit(payload)

    def _on_ready(self, payload: Payload) -> None:
        """记下会话凭据。

        Raises:
            GatewayError: READY 的数据无法解析。没有 session_id 便无从恢复会话。
        """

        try:
            ready = ReadyData.model_validate(payload.d)
        except ValidationError as e:
            raise GatewayError(f"READY 的数据无法解析：{e}") from e

        self._session_id = ready.session_id
        self._authenticated = True

        logger.info("网关鉴权成功，session_id={}", ready.session_id)

    def _on_closed(self, closed: ConnectionClosed) -> None:
        """按关闭码决定后续处置。

        Raises:
            GatewayRejectedError: 关闭码表明重连不会恢复。
        """

        if isinstance(closed, ConnectionClosedOK):
            logger.debug("网关连接已正常关闭")

            return

        if closed.rcvd is None:
            # 没有收到关闭帧，多为网络中断，重连即可。
            logger.warning("网关连接异常断开：{}", closed)

            return

        code = closed.rcvd.code
        reason = closed.rcvd.reason or "无描述"

        if code in _FATAL_CLOSE_CODES:
            raise GatewayRejectedError(f"关闭码 {code}：{reason}")

        if code == CloseCode.AUTHENTICATION_FAILED:
            # token 可能已被平台吊销，本地记录的有效期不再可信。
            logger.warning("网关鉴权失败，作废 access token 后重连：{}", reason)
            self._token_provider.invalidate()
        elif code in _SESSION_INVALID_CLOSE_CODES:
            logger.warning("网关会话不可续传（关闭码 {}：{}），重连后将重新鉴权", code, reason)
            self._forget_session()
        else:
            logger.warning("网关连接被关闭（关闭码 {}）：{}", code, reason)

    def _forget_session(self) -> None:
        """丢弃会话凭据，下次连接改为重新鉴权。"""

        self._session_id = None
        self._last_seq = None

    @staticmethod
    async def _send(connection: ClientConnection, frame: dict[str, Any]) -> None:
        # 上行帧不记录内容：鉴权与恢复都带 token。
        await connection.send(json.dumps(frame))

    @staticmethod
    def _parse(frame: Data) -> Payload | None:
        """解析一个下行帧，无法解析时返回 ``None``。"""

        text = frame.decode("utf-8", "replace") if isinstance(frame, bytes) else frame

        # 平台实际下发的字段与文档时有出入，排查时需要原始报文；用 lazy 求值，未开启该
        # 级别时不做拼装。
        logger.opt(lazy=True).trace("下行帧 {}", lambda: preview(text))

        try:
            envelope = json.loads(text)
        except ValueError as e:
            logger.warning("网关下发的帧不是合法 JSON：{}", e)

            return None

        if not isinstance(envelope, dict):
            logger.warning("网关下发的帧不是 JSON 对象")

            return None

        # RESUMED 一类的帧把 d 下发为字符串或空数组。这些帧的数据本项目并不使用，一律按
        # 空对象处理，不因形状不符丢掉整帧。
        if not isinstance(envelope.get("d"), dict):
            envelope["d"] = {}

        try:
            return Payload.model_validate(envelope)
        except ValidationError as e:
            logger.warning("网关下发的 payload 无法解析：{}", e)

            return None
