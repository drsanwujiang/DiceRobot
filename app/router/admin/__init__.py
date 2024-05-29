from fastapi import APIRouter, Depends

from ...log import logger
from ...auth import verify_password, generate_jwt_token, verify_jwt_token
from ...config import status, replies, settings, plugin_settings, chat_settings
from ...exceptions import ParametersInvalidError
from ...models.admin import AuthRequest
from .. import Response


router = APIRouter()


@router.post("/auth")
async def auth(request: AuthRequest) -> Response:
    if not verify_password(request.password):
        logger.warning("Failed authentication attempt")
        raise ParametersInvalidError(message="Wrong password")

    logger.success("Successful authentication")

    return Response(data={"token": generate_jwt_token()})


@router.get("/status", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_status() -> Response:
    return Response(data=status.model_dump())


@router.get("/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_settings() -> Response:
    return Response(data=settings.model_dump())


@router.get("/plugin-settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_plugin_settings() -> Response:
    return Response(data=plugin_settings.dict())


@router.get("/chat-settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_chat_settings() -> Response:
    return Response(data=chat_settings.dict())


@router.get("/reply", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_reply() -> Response:
    return Response(data=replies.dict())
