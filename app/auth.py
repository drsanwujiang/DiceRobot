from datetime import datetime, timedelta
import hmac

from fastapi import Request, Header
from werkzeug.security import generate_password_hash, check_password_hash
import jwt

from .config import status, settings
from .exceptions import TokenInvalidError, SignatureInvalidError


def generate_password(password: str) -> str:
    return generate_password_hash(password)


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
    scheme, _, token = authorization.partition(" ")

    if not authorization or scheme != "Bearer" or not token:
        raise TokenInvalidError

    try:
        jwt.decode(token, settings.security.jwt.secret, settings.security.jwt.algorithm)
    except jwt.InvalidTokenError:
        raise TokenInvalidError


async def verify_signature(
    request: Request,
    signature: str = Header(alias="X-Signature", min_length=45, max_length=45)
) -> None:
    if status.debug:
        return

    signature = signature[5:]
    digest = hmac.digest(
        settings.security.webhook.secret.encode(),
        await request.body(),
        "sha1"
    ).hex()

    if signature != digest:
        raise SignatureInvalidError
