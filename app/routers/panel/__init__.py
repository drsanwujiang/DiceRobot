from fastapi import APIRouter

from ...config import status, replies, settings, plugin_settings, chat_settings
from ...routers import Response


router = APIRouter()


@router.get("/status")
async def get_status() -> Response:
    return Response(data=status)


@router.get("/reply")
async def get_reply() -> Response:
    return Response(data=replies)


@router.get("/settings")
async def get_settings() -> Response:
    return Response(data=settings)


@router.get("/settings/plugin")
async def get_plugin_settings() -> Response:
    return Response(data=plugin_settings)


@router.get("/settings/chat")
async def get_chat_settings() -> Response:
    return Response(data=chat_settings)
