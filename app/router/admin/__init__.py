from fastapi import APIRouter, Depends

from ...log import logger
from ...auth import verify_password, generate_jwt_token, verify_jwt_token
from ...config import status, replies, settings, plugin_settings, chat_settings
from ...dispatch import dispatcher
from ...exceptions import ParametersInvalidError, ResourceNotFoundError
from ...models.admin import (
    AuthRequest, SetModuleStatusRequest, UpdateSettingsRequest
)
from ...enum import ChatType
from .. import Response


router = APIRouter()


@router.post("/auth")
async def auth(data: AuthRequest) -> Response:
    logger.info("Admin request received: auth")

    if not verify_password(data.password):
        logger.warning("Authentication failed")
        raise ParametersInvalidError(message="Wrong password")

    logger.success("Authentication succeeded")

    return Response(data={"token": generate_jwt_token()})


@router.get("/status", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_status() -> Response:
    logger.info("Admin request received: get status")

    return Response(data=status.model_dump())


@router.post("/status/module", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def set_module_status(data: SetModuleStatusRequest) -> Response:
    logger.info("Admin request received: set module status")

    status.module.order = data.order
    status.module.event = data.event

    return Response()


@router.get("/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_settings() -> Response:
    logger.info("Admin request received: get settings")

    return Response(data=settings.model_dump())


@router.patch("/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_settings(data: UpdateSettingsRequest) -> Response:
    logger.info("Admin request received: update settings")

    settings.update(data.model_dump())

    return Response()


@router.get("/plugins", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_plugin_list() -> Response:
    logger.info("Admin request received: get plugin list")

    return Response(data={name: plugin.model_dump() for name, plugin in status.plugins.items()})


@router.get("/plugin/{plugin}", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_plugin_list(plugin: str) -> Response:
    logger.info(f"Admin request received: get plugin, plugin: {plugin}")

    if plugin not in status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    return Response(data=status.plugins[plugin].model_dump())


@router.get("/plugin/{plugin}/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_plugin_settings(plugin: str) -> Response:
    logger.info(f"Admin request received: get plugin settings, plugin: {plugin}")

    if plugin not in status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    return Response(data=plugin_settings.get(plugin=plugin))


@router.patch("/plugin/{plugin}/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_plugin_settings(plugin: str, data: dict) -> Response:
    logger.info(f"Admin request received: update plugin settings, plugin: {plugin}")

    if plugin not in status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    plugin_settings.set(plugin=plugin, settings=data)
    dispatcher.find_plugin(plugin).load()

    return Response()


@router.get("/plugin/{plugin}/replies", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_plugin_replies(plugin: str) -> Response:
    logger.info(f"Admin request received: get plugin replies, plugin: {plugin}")

    if plugin not in status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    return Response(data=replies.get(reply_group=plugin))


@router.patch("/plugin/{plugin}/replies", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_plugin_replies(plugin: str, data: dict[str, str]) -> Response:
    logger.info(f"Admin request received: update plugin replies, plugin: {plugin}")

    if plugin not in status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    replies.set(reply_group=plugin, replies=data)
    dispatcher.find_plugin(plugin).load()

    return Response()


@router.get("/chat/{chat_type}/{chat_id}/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_chat_settings(chat_type: ChatType, chat_id: int, group: str) -> Response:
    logger.info(f"Admin request received: get chat settings, chat type: {chat_type.value}, chat ID: {chat_id}, setting group: {group}")

    return Response(data=chat_settings.get(chat_type=chat_type, chat_id=chat_id, setting_group=group))
