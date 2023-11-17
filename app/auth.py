import time
import jwt

from fastapi import Request
from fastapi.security import HTTPBearer as _HTTPBearer, HTTPAuthorizationCredentials

from .config import settings
from .exceptions import TokenInvalidError, AuthenticationError


def verify_token(request: Request) -> None:
    token: str = request.query_params.get("token")

    if not token:
        raise TokenInvalidError()
    elif token != settings["security"]["webhook"]["token"]:
        raise AuthenticationError()


class HTTPBearer(_HTTPBearer):
    async def __call__(self, request: Request) -> HTTPAuthorizationCredentials:
        authorization = request.headers.get("Authorization")

        if not authorization:
            raise TokenInvalidError()

        scheme, _, credentials = authorization.partition(" ")

        if scheme != "Bearer" or not credentials:
            raise TokenInvalidError()

        return HTTPAuthorizationCredentials(scheme=scheme, credentials=credentials)


class JWTBearer(HTTPBearer):
    @staticmethod
    def decode_jwt(token: str) -> dict:
        try:
            decoded_token = jwt.decode(token, settings["security"]["jwt"]["secret"], algorithms=settings["security"]["jwt"]["algorithm"])
            return decoded_token if decoded_token["expires"] >= time.time() else None
        except:
            return {}

    @staticmethod
    def verify_jwt(jwt_token: str) -> bool:
        is_token_valid: bool = False

        try:
            payload = JWTBearer.decode_jwt(jwt_token)
        except:
            payload = None
        if payload:
            is_token_valid = True

        return is_token_valid

    async def __call__(self, request: Request) -> str:
        credentials: HTTPAuthorizationCredentials = await super().__call__(request)

        if not self.verify_jwt(credentials.credentials):
            raise TokenInvalidError()

        return credentials.credentials
