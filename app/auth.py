from datetime import datetime, timedelta
import hmac

from fastapi import Request, Header
from werkzeug.security import check_password_hash
import jwt

from .log import logger
from .config import status, settings
from .exceptions import TokenInvalidError, SignatureInvalidError


def verify_password(password: str) -> bool:
    if not settings.security.admin.password_hash:
        return True

    return check_password_hash(settings.security.admin.password_hash, password)


def generate_jwt_token() -> str:
    return jwt.encode(
        {"exp": datetime.now() + timedelta(days=7)},
        settings.security.jwt.secret,
        settings.security.jwt.algorithm
    )


def verify_jwt_token(authorization: str = Header()) -> None:
    logger.debug("HTTP request received, verify JWT token")

    scheme, _, token = authorization.partition(" ")

    if not authorization or scheme != "Bearer" or not token:
        logger.debug("JWT token verification failed, token not exists")
        raise TokenInvalidError

    try:
        jwt.decode(token, settings.security.jwt.secret, settings.security.jwt.algorithm)
    except jwt.InvalidTokenError:
        logger.debug("JWT token verification failed, token invalid")
        raise TokenInvalidError

    logger.debug("JWT token verification passed")


async def verify_signature(
    request: Request,
    signature: str = Header(alias="X-Signature", min_length=45, max_length=45)
) -> None:
    logger.debug("HTTP request received, verify signature")

    if status.debug:
        logger.debug("Signature verification passed, debug mode")
        return

    signature = signature[5:]
    digest = hmac.digest(
        settings.security.webhook.secret.encode(),
        await request.body(),
        "sha1"
    ).hex()

    if signature != digest:
        logger.debug("Signature verification failed, signature invalid")
        raise SignatureInvalidError

    logger.debug("Signature verification passed")
