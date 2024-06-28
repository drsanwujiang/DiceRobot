import signal

from fastapi import APIRouter, Depends

from ..log import logger
from ..auth import verify_password, generate_jwt_token, verify_jwt_token
from ..config import status, replies, settings, plugin_settings, chat_settings
from ..dispatch import dispatcher
from ..exceptions import ParametersInvalidError, ResourceNotFoundError
from ..enum import ChatType
from ..models.panel.admin import (
    AuthRequest, SetModuleStatusRequest, UpdateSecuritySettingsRequest, UpdateApplicationSettingsRequest
)
from . import JSONResponse

router = APIRouter()


@router.post("/auth")
async def auth(data: AuthRequest) -> JSONResponse:
    logger.info("Admin request received: auth")

    if not verify_password(data.password):
        logger.warning("Authentication failed")
        raise ParametersInvalidError(message="Wrong password")

    logger.success("Authentication succeeded")

    return JSONResponse(data={
        "token": generate_jwt_token()
    })


@router.get("/status", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_status() -> JSONResponse:
    logger.info("Admin request received: get status")

    return JSONResponse(data=status.model_dump())


@router.post("/status/module", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def set_module_status(data: SetModuleStatusRequest) -> JSONResponse:
    logger.info("Admin request received: set module status")

    status.module.order = data.order
    status.module.event = data.event

    return JSONResponse()


@router.patch("/settings/security", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_security_settings(data: UpdateSecuritySettingsRequest) -> JSONResponse:
    logger.info("Admin request received: update security settings")

    settings.update_security(data.model_dump(exclude_none=True))

    return JSONResponse()


@router.get("/settings/app", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_settings() -> JSONResponse:
    logger.info("Admin request received: get application settings")

    return JSONResponse(data=settings.app.model_dump())


@router.patch("/settings/app", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_application_settings(data: UpdateApplicationSettingsRequest) -> JSONResponse:
    logger.info("Admin request received: update application settings")

    settings.update_application(data.model_dump(exclude_none=True))

    return JSONResponse()


@router.get("/plugins", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_plugin_list() -> JSONResponse:
    logger.info("Admin request received: get plugin list")

    return JSONResponse(data={name: plugin.model_dump() for name, plugin in status.plugins.items()})


@router.get("/plugin/{plugin}", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_plugin_list(plugin: str) -> JSONResponse:
    logger.info(f"Admin request received: get plugin, plugin: {plugin}")

    if plugin not in status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    return JSONResponse(data=status.plugins[plugin].model_dump())


@router.get("/plugin/{plugin}/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_plugin_settings(plugin: str) -> JSONResponse:
    logger.info(f"Admin request received: get plugin settings, plugin: {plugin}")

    if plugin not in status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    return JSONResponse(data=plugin_settings.get(plugin=plugin))


@router.patch("/plugin/{plugin}/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_plugin_settings(plugin: str, data: dict) -> JSONResponse:
    logger.info(f"Admin request received: update plugin settings, plugin: {plugin}")

    if plugin not in status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    plugin_settings.set(plugin=plugin, settings=data)
    dispatcher.find_plugin(plugin).load()

    return JSONResponse()


@router.get("/plugin/{plugin}/replies", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_plugin_replies(plugin: str) -> JSONResponse:
    logger.info(f"Admin request received: get plugin replies, plugin: {plugin}")

    if plugin not in status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    return JSONResponse(data=replies.get(reply_group=plugin))


@router.patch("/plugin/{plugin}/replies", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_plugin_replies(plugin: str, data: dict[str, str]) -> JSONResponse:
    logger.info(f"Admin request received: update plugin replies, plugin: {plugin}")

    if plugin not in status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    replies.set(reply_group=plugin, replies=data)
    dispatcher.find_plugin(plugin).load()

    return JSONResponse()


@router.get("/chat/{chat_type}/{chat_id}/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_chat_settings(chat_type: ChatType, chat_id: int, group: str) -> JSONResponse:
    logger.info(f"Admin request received: get chat settings, chat type: {chat_type.value}, chat ID: {chat_id}, setting group: {group}")

    return JSONResponse(data=chat_settings.get(chat_type=chat_type, chat_id=chat_id, setting_group=group))


@router.post("/stop", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def stop() -> JSONResponse:
    logger.info("Admin request received: stop")

    signal.raise_signal(signal.SIGTERM)

    return JSONResponse()
