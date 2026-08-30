"""签名模块的测试。

测试签名由独立构造的密钥生成，以验证平台约定的三条规则：种子派生方式、验签的拼接
顺序、challenge 的拼接顺序。
"""

from __future__ import annotations

import pytest
from cryptography.exceptions import InvalidSignature
from cryptography.hazmat.primitives.asymmetric.ed25519 import Ed25519PrivateKey

from dicerobot.errors import ConfigurationError, SignatureError
from dicerobot.qq.signature import sign_challenge, verify_signature

SECRET = "abc"
# "abc" 重复拼接至不短于 32 字节后截断，即 "abc" 无限重复的前 32 字节。
EXPECTED_SEED = b"abcabcabcabcabcabcabcabcabcabcab"


@pytest.fixture
def key() -> Ed25519PrivateKey:
    """独立构造的同一把密钥，用于生成测试签名。"""

    return Ed25519PrivateKey.from_private_bytes(EXPECTED_SEED)


def sign(key: Ed25519PrivateKey, timestamp: str, body: bytes) -> str:
    return key.sign(timestamp.encode() + body).hex()


class TestSeedDerivation:
    def test_short_secret_is_repeated_to_32_bytes(self) -> None:
        assert len(EXPECTED_SEED) == 32

        # 派生实现有误时，独立构造的密钥所签的签名将无法通过验签。
        key = Ed25519PrivateKey.from_private_bytes(EXPECTED_SEED)
        verify_signature(SECRET, signature=sign(key, "1", b"{}"), timestamp="1", body=b"{}")

    def test_long_secret_is_truncated_to_32_bytes(self) -> None:
        secret = "x" * 40
        key = Ed25519PrivateKey.from_private_bytes(secret.encode()[:32])

        verify_signature(secret, signature=sign(key, "1", b"{}"), timestamp="1", body=b"{}")

    def test_empty_secret_is_rejected(self) -> None:
        # 空 secret 会使派生时的拼接循环无法终止。
        with pytest.raises(ConfigurationError):
            verify_signature("", signature="00", timestamp="1", body=b"{}")


class TestVerifySignature:
    def test_accepts_valid_signature(self, key: Ed25519PrivateKey) -> None:
        body = b'{"op":0,"d":{}}'
        verify_signature(SECRET, signature=sign(key, "1730000000", body), timestamp="1730000000", body=body)

    def test_rejects_tampered_body(self, key: Ed25519PrivateKey) -> None:
        signature = sign(key, "1730000000", b'{"op":0}')

        with pytest.raises(SignatureError):
            verify_signature(SECRET, signature=signature, timestamp="1730000000", body=b'{"op":1}')

    def test_rejects_tampered_timestamp(self, key: Ed25519PrivateKey) -> None:
        signature = sign(key, "1730000000", b"{}")

        with pytest.raises(SignatureError):
            verify_signature(SECRET, signature=signature, timestamp="1730000001", body=b"{}")

    def test_rejects_signature_from_another_secret(self) -> None:
        other = Ed25519PrivateKey.from_private_bytes(b"z" * 32)

        with pytest.raises(SignatureError):
            verify_signature(SECRET, signature=sign(other, "1", b"{}"), timestamp="1", body=b"{}")

    @pytest.mark.parametrize("signature", ["", "not-hex", "abc"])
    def test_rejects_malformed_signature(self, signature: str) -> None:
        with pytest.raises(SignatureError):
            verify_signature(SECRET, signature=signature, timestamp="1", body=b"{}")

    def test_rejects_missing_timestamp(self, key: Ed25519PrivateKey) -> None:
        with pytest.raises(SignatureError):
            verify_signature(SECRET, signature=sign(key, "", b"{}"), timestamp="", body=b"{}")

    def test_body_must_be_the_raw_bytes(self, key: Ed25519PrivateKey) -> None:
        """反序列化后重新序列化会改变字节，导致验签失败。"""

        raw = b'{"op": 0, "d": {}}'
        signature = sign(key, "1", raw)
        reserialized = b'{"op":0,"d":{}}'  # json.dumps 的默认紧凑形式

        verify_signature(SECRET, signature=signature, timestamp="1", body=raw)

        with pytest.raises(SignatureError):
            verify_signature(SECRET, signature=signature, timestamp="1", body=reserialized)


class TestSignChallenge:
    def test_signs_event_ts_followed_by_plain_token(self, key: Ed25519PrivateKey) -> None:
        plain_token = "Arq0D5A61EgUu4OxUvOp"
        event_ts = "1725442341"

        signature = bytes.fromhex(sign_challenge(SECRET, plain_token=plain_token, event_ts=event_ts))

        # 顺序为 event_ts + plain_token。
        key.public_key().verify(signature, (event_ts + plain_token).encode())

        with pytest.raises(InvalidSignature):
            key.public_key().verify(signature, (plain_token + event_ts).encode())

    def test_is_deterministic(self) -> None:
        # Ed25519 的签名是确定性的，平台可能重复下发同一个 challenge。
        first = sign_challenge(SECRET, plain_token="t", event_ts="1")
        second = sign_challenge(SECRET, plain_token="t", event_ts="1")

        assert first == second
