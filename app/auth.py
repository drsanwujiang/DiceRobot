from typing import Annotated, Any
import hmac

from loguru import logger
from fastapi import Request, Header, Depends
from werkzeug.security import check_password_hash
import jwt
import arrow

from .dependencies import AppContextDep
from .exceptions import TokenInvalidError, SignatureInvalidError

__all__ = [
    "Auth",
    "verify_signature",
    "verify_jwt_token"
]


class Auth:
    def __init__(self, context: AppContextDep) -> None:
        self.context = context

    def verify_password(self, password: str) -> bool:
        if not self.context.settings.security.admin.password_hash:
            return True

        return check_password_hash(self.context.settings.security.admin.password_hash, password)

    def calculate_signature(self, algorithm: str, data: bytes) -> str:
        return hmac.digest(self.context.settings.security.webhook.secret.encode(), data, algorithm).hex()

    def generate_jwt_token(self) -> str:
        payload = {
            "iss": "DiceRobot",
            "iat": arrow.now().timestamp(),
            "exp": arrow.now().shift(seconds=self.context.settings.security.jwt.expiration).timestamp()
        }

        return jwt.encode(
            payload=payload,
            key=self.context.settings.security.jwt.secret,
            algorithm=self.context.settings.security.jwt.algorithm
        )

    def decode_jwt_token(self, token: str) -> Any:
        return jwt.decode(
            jwt=token,
            key=self.context.settings.security.jwt.secret,
            algorithms=[self.context.settings.security.jwt.algorithm],
            options={
                "require": ["iss", "iat", "exp"]
            },
            issuer="DiceRobot"
        )


async def verify_signature(
    request: Request,
    signature: Annotated[str, Header(alias="X-Signature", min_length=42, max_length=48)],
    auth: Annotated[Auth, Depends()]
) -> None:
    logger.debug("HTTP request received, verify signature")

    try:
        algorithm, digest = signature.split("=", 1)

        if digest != auth.calculate_signature(algorithm, await request.body()):
            raise ValueError
    except ValueError:
        logger.debug("Signature verification failed, signature invalid")
        raise SignatureInvalidError

    logger.debug("Signature verification passed")


def verify_jwt_token(
    auth: Annotated[Auth, Depends()],
    authorization: Annotated[str, Header()] = None
) -> None:
    logger.debug("HTTP request received, verify JWT token")

    if not authorization:
        logger.debug("JWT token verification failed, token not exists")
        raise TokenInvalidError

    scheme, _, token = authorization.partition(" ")

    if scheme != "Bearer" or not token:
        logger.debug("JWT token verification failed, token invalid")
        raise TokenInvalidError

    try:
        auth.decode_jwt_token(token)
    except jwt.InvalidTokenError:
        logger.debug("JWT token verification failed, token invalid")
        raise TokenInvalidError

    logger.debug("JWT token verification passed")
