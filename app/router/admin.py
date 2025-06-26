from typing import Annotated
from datetime import date
import asyncio

from fastapi import APIRouter, Depends, Query, Request

from ..log import logger, load_logs
from ..auth import verify_password, generate_jwt_token, verify_jwt_token
from ..config import status, replies, settings, plugin_settings, chat_settings
from ..dispatch import dispatcher
from ..exceptions import ParametersInvalidError, ResourceNotFoundError, BadRequestError
from ..manage import dicerobot_manager
from ..enum import ChatType, UpdateStatus
from ..models.panel.admin import (
    AuthRequest, SetModuleStatusRequest, UpdateSecuritySettingsRequest, UpdateApplicationSettingsRequest
)
from . import JSONResponse, StreamingResponse

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


@router.get("/logs", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_logs(date_: Annotated[date, Query(alias="date")]) -> JSONResponse:
    logger.info(f"Admin request received: get logs, date: {date_}")

    if (logs := load_logs(date_)) is None:
        raise ResourceNotFoundError(message="Logs not found")
    elif logs is False:
        raise BadRequestError(message="Log file too large")

    return JSONResponse(data=logs)


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

    if plugin in dispatcher.order_plugins:
        plugin_class = dispatcher.order_plugins[plugin]

        return JSONResponse(data={
            **status.plugins[plugin].model_dump(),
            "orders": plugin_class.orders,
            "priority": plugin_class.priority
        })
    elif plugin in dispatcher.event_plugins:
        plugin_class = dispatcher.event_plugins[plugin]

        return JSONResponse(data={
            **status.plugins[plugin].model_dump(),
            "events": [event.__name__ for event in plugin_class.events]
        })
    else:
        raise ResourceNotFoundError(message="Plugin not found")


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


@router.post("/plugin/{plugin}/settings/reset", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def reset_plugin_settings(plugin: str) -> JSONResponse:
    logger.info(f"Admin request received: reset plugin settings, plugin: {plugin}")

    if plugin not in status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    plugin_settings.set(plugin=plugin, settings={})
    dispatcher.find_plugin(plugin).load()

    return JSONResponse()


@router.get("/plugin/{plugin}/replies", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_plugin_replies(plugin: str) -> JSONResponse:
    logger.info(f"Admin request received: get plugin replies, plugin: {plugin}")

    if plugin not in status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    return JSONResponse(data=replies.get_replies(group=plugin))


@router.patch("/plugin/{plugin}/replies", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_plugin_replies(plugin: str, data: dict[str, str]) -> JSONResponse:
    logger.info(f"Admin request received: update plugin replies, plugin: {plugin}")

    if plugin not in status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    replies.set_replies(group=plugin, replies=data)
    dispatcher.find_plugin(plugin).load()

    return JSONResponse()


@router.post("/plugin/{plugin}/replies/reset", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def reset_plugin_replies(plugin: str) -> JSONResponse:
    logger.info(f"Admin request received: reset plugin replies, plugin: {plugin}")

    if plugin not in status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    replies.set_replies(group=plugin, replies={})
    dispatcher.find_plugin(plugin).load()

    return JSONResponse()


@router.get("/chat/{chat_type}/{chat_id}/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_chat_settings(chat_type: ChatType, chat_id: int, group: str) -> JSONResponse:
    logger.info(f"Admin request received: get chat settings, chat type: {chat_type.value}, chat ID: {chat_id}, setting group: {group}")

    return JSONResponse(data=chat_settings.get(chat_type=chat_type, chat_id=chat_id, setting_group=group))


@router.post("/update", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update(request: Request) -> StreamingResponse:
    logger.info("Admin request received: update")

    task = asyncio.create_task(dicerobot_manager.update())

    async def content_generator():
        while await request.is_disconnected():
            yield {"status": str(dicerobot_manager.update_status)}

            if dicerobot_manager.update_status in [UpdateStatus.COMPLETED, UpdateStatus.FAILED]:
                dicerobot_manager.update_status = UpdateStatus.NONE
                break
            elif task.done() and dicerobot_manager.update_status != UpdateStatus.COMPLETED:
                dicerobot_manager.update_status = UpdateStatus.FAILED
                continue

            await asyncio.sleep(1)

    return StreamingResponse(content_generator())


@router.post("/restart", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def restart() -> JSONResponse:
    logger.info("Admin request received: restart")

    await dicerobot_manager.restart()

    return JSONResponse()


@router.post("/stop", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def stop() -> JSONResponse:
    logger.info("Admin request received: stop")

    await dicerobot_manager.stop()

    return JSONResponse()
