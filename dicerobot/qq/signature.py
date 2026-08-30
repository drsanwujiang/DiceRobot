"""Webhook 的 Ed25519 签名校验与回调地址校验。

平台以 AppSecret 派生一对 Ed25519 密钥，双向使用：推送请求携带签名供本端用公钥
校验；配置回调地址时下发 challenge（``op = 13``），需用私钥签名后回传。

Ed25519 种子固定 32 字节，而 AppSecret 通常更短，因此需重复拼接至不短于 32 字节
后截断。
"""

from __future__ import annotations

from functools import lru_cache

from cryptography.exceptions import InvalidSignature
from cryptography.hazmat.primitives.asymmetric.ed25519 import Ed25519PrivateKey

from dicerobot.errors import ConfigurationError, SignatureError

__all__ = [
    "SIGNATURE_HEADER",
    "TIMESTAMP_HEADER",
    "sign_challenge",
    "verify_signature",
]

SIGNATURE_HEADER = "X-Signature-Ed25519"
TIMESTAMP_HEADER = "X-Signature-Timestamp"

_SEED_SIZE = 32


@lru_cache(maxsize=4)
def _derive_signing_key(secret: str) -> Ed25519PrivateKey:
    """由 AppSecret 派生签名私钥。结果带缓存，每个 webhook 请求均需使用。"""

    if not secret:
        # 空 secret 会使下方的拼接循环无法终止。
        raise ConfigurationError("AppSecret 为空，无法派生签名密钥")

    seed = secret.encode()

    while len(seed) < _SEED_SIZE:
        seed *= 2

    return Ed25519PrivateKey.from_private_bytes(seed[:_SEED_SIZE])


def verify_signature(secret: str, *, signature: str, timestamp: str, body: bytes) -> None:
    """校验平台推送请求的签名。

    待签名内容为 ``timestamp`` 与原始请求体字节的拼接。``body`` 必须是未经解析的
    字节，反序列化后重新序列化会改变字节从而导致校验失败。

    Args:
        secret: AppSecret。
        signature: ``X-Signature-Ed25519`` 请求头，十六进制字符串。
        timestamp: ``X-Signature-Timestamp`` 请求头。
        body: 原始请求体字节。

    Raises:
        SignatureError: 签名格式非法或校验未通过。
    """

    if not signature or not timestamp:
        raise SignatureError("缺少签名或时间戳请求头")

    try:
        signature_bytes = bytes.fromhex(signature)
    except ValueError as e:
        raise SignatureError("签名不是合法的十六进制字符串") from e

    try:
        _derive_signing_key(secret).public_key().verify(signature_bytes, timestamp.encode() + body)
    except InvalidSignature as e:
        raise SignatureError("签名校验未通过") from e


def sign_challenge(secret: str, *, plain_token: str, event_ts: str) -> str:
    """为回调地址校验生成签名。

    待签名内容为 ``event_ts + plain_token``，顺序与 :func:`verify_signature` 相反。

    Args:
        secret: AppSecret。
        plain_token: 平台下发的 ``d.plain_token``。
        event_ts: 平台下发的 ``d.event_ts``。

    Returns:
        十六进制签名，需与原样返回的 ``plain_token`` 一并响应。
    """

    return _derive_signing_key(secret).sign((event_ts + plain_token).encode()).hex()
