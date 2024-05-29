from datetime import datetime, timedelta

from fastapi import Request
from werkzeug.security import generate_password_hash, check_password_hash
import jwt

from .config import settings
from .exceptions import TokenInvalidError


def generate_password(password: str) -> str:
    return generate_password_hash(password)


def verify_password(password: str) -> bool:
    if not settings.security.admin.password:
        return True

    return check_password_hash(settings.security.admin.password, password)


def generate_jwt_token() -> str:
    return jwt.encode(
        {"exp": datetime.now() + timedelta(days=7)},
        settings.security.jwt.secret,
        settings.security.jwt.algorithm
    )


def verify_jwt_token(request: Request) -> None:
    authorization = request.headers.get("Authorization", "")
    scheme, _, token = authorization.partition(" ")

    if not authorization or scheme != "Bearer" or not token:
        raise TokenInvalidError

    try:
        jwt.decode(token, settings.security.jwt.secret, settings.security.jwt.algorithm)
    except jwt.InvalidTokenError:
        raise TokenInvalidError


def verify_webhook_token(request: Request) -> None:
    token: str = request.query_params.get("token")

    if not token or token != settings.security.webhook.token:
        raise TokenInvalidError
