from typing import Annotated

from loguru import logger
from fastapi import APIRouter, Depends, Query

from ..auth import Auth, verify_jwt_token
from ..dependencies import AppContextDep
from ..exceptions import ParametersInvalidError, ResourceNotFoundError
from ..responses import JSONResponse, EventSourceResponse
from ..enum import ChatType
from ..models.router.admin import (
    AuthRequest, SetModuleStatusRequest, UpdateSecuritySettingsRequest, UpdateApplicationSettingsRequest,
    UpdatePluginSettingsRequest
)

router = APIRouter()


@router.post("/auth")
async def authenticate(data: AuthRequest, auth: Annotated[Auth, Depends()]) -> JSONResponse:
    logger.info("API request received: Authenticate")

    if not auth.verify_password(data.password):
        logger.warning("Authentication failed")
        raise ParametersInvalidError(message="Wrong password")

    logger.success("Authentication succeeded")

    return JSONResponse(data={
        "token": auth.generate_jwt_token()
    })


@router.get("/status", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_status(context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Get application status")

    return JSONResponse(data=context.status.model_dump())


@router.post("/status/module", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def set_module_status(data: SetModuleStatusRequest, context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Set module status")

    context.status.module.order = data.order
    context.status.module.event = data.event

    return JSONResponse()


@router.patch("/settings/security", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_security_settings(data: UpdateSecuritySettingsRequest, context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Update security settings")

    context.settings.update_security(data.model_dump(exclude_none=True))
    context.config_manager.dirty = True

    return JSONResponse()


@router.get("/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_application_settings(context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Get application settings")

    return JSONResponse(data=context.settings.app.model_dump())


@router.patch("/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_application_settings(data: UpdateApplicationSettingsRequest, context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Update application settings")

    context.settings.update_application(data.model_dump(exclude_none=True))
    context.config_manager.dirty = True

    return JSONResponse()


@router.get("/plugins", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_plugin_list(context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Get plugin list")

    return JSONResponse(data={name: plugin.model_dump() for name, plugin in context.status.plugins.items()})


@router.get("/plugin/{plugin}", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_plugin(plugin: str, context: AppContextDep) -> JSONResponse:
    logger.info(f"API request received: Get plugin info for \"{plugin}\"")

    if plugin in context.dispatch_manager.order_plugins:
        plugin_class = context.dispatch_manager.order_plugins[plugin]

        return JSONResponse(data={
            **context.status.plugins[plugin].model_dump(),
            "orders": plugin_class.orders,
            "priority": plugin_class.priority
        })
    elif plugin in context.dispatch_manager.event_plugins:
        plugin_class = context.dispatch_manager.event_plugins[plugin]

        return JSONResponse(data={
            **context.status.plugins[plugin].model_dump(),
            "events": [event.__name__ for event in plugin_class.events]
        })
    else:
        raise ResourceNotFoundError(message="Plugin not found")


@router.get("/plugin/{plugin}/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_plugin_settings(plugin: str, context: AppContextDep) -> JSONResponse:
    logger.info(f"API request received: Get plugin settings for \"{plugin}\"")

    if plugin not in context.status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    return JSONResponse(data=context.plugin_settings.get(plugin=plugin))


@router.patch("/plugin/{plugin}/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_plugin_settings(plugin: str, data: UpdatePluginSettingsRequest, context: AppContextDep) -> JSONResponse:
    logger.info(f"API request received: Update plugin settings for \"{plugin}\"")

    if plugin not in context.status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    plugin_class = context.dispatch_manager.find_plugin(plugin)

    for key in data.model_extra:
        if key not in plugin_class.default_plugin_settings:
            raise ParametersInvalidError(message="Plugin settings invalid")

    context.plugin_settings.set(plugin=plugin, settings=data)
    context.config_manager.dirty = True
    plugin_class.load(context)

    return JSONResponse()


@router.post("/plugin/{plugin}/settings/reset", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def reset_plugin_settings(plugin: str, context: AppContextDep) -> JSONResponse:
    logger.info(f"API request received: Reset plugin settings for \"{plugin}\"")

    if plugin not in context.status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    context.plugin_settings.set(plugin=plugin, settings={})
    context.config_manager.dirty = True
    context.dispatch_manager.find_plugin(plugin).load(context)

    return JSONResponse()


@router.get("/plugin/{plugin}/replies", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_plugin_replies(plugin: str, context: AppContextDep) -> JSONResponse:
    logger.info(f"API request received: Get plugin replies for \"{plugin}\"")

    if plugin not in context.status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    return JSONResponse(data=context.replies.get_replies(group=plugin))


@router.patch("/plugin/{plugin}/replies", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_plugin_replies(plugin: str, data: dict[str, str], context: AppContextDep) -> JSONResponse:
    logger.info(f"API request received: Update plugin replies for \"{plugin}\"")

    if plugin not in context.status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    plugin_class = context.dispatch_manager.find_plugin(plugin)

    for key in data.keys():
        if key not in plugin_class.default_replies:
            raise ParametersInvalidError(message="Plugin replies invalid")

    context.replies.set_replies(group=plugin, replies=data)
    context.config_manager.dirty = True
    plugin_class.load(context)

    return JSONResponse()


@router.post("/plugin/{plugin}/replies/reset", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def reset_plugin_replies(plugin: str, context: AppContextDep) -> JSONResponse:
    logger.info(f"API request received: Reset plugin replies for \"{plugin}\"")

    if plugin not in context.status.plugins:
        raise ResourceNotFoundError(message="Plugin not found")

    context.replies.set_replies(group=plugin, replies={})
    context.dispatch_manager.find_plugin(plugin).load(context)

    return JSONResponse()


@router.get("/chat/{chat_type}/{chat_id}/settings/{group}", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_chat_settings(chat_type: ChatType, chat_id: int, group: str, context: AppContextDep) -> JSONResponse:
    logger.info(f"API request received: Get chat settings for \"{chat_id} ({chat_type.value})\" with settings group \"{group}\"")

    return JSONResponse(data=context.chat_settings.get(chat_type=chat_type, chat_id=chat_id, settings_group=group))


@router.get("/logs", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_logs(date: Annotated[str, Query(pattern=r"\d{4}-\d{2}-\d{2}")], context: AppContextDep) -> EventSourceResponse:
    logger.info(f"API request received: Get logs for \"{date}\"")

    if not context.app_actuator.check_log_file(filename := f"dicerobot-{date}.log"):
        raise ResourceNotFoundError(message="Logs not found")

    return EventSourceResponse(context.app_actuator.create_logs_stream(filename))


@router.post("/update", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update(context: AppContextDep) -> EventSourceResponse:
    logger.info("API request received: Update application")

    return EventSourceResponse(context.app_actuator.create_update_stream())


@router.post("/restart", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def restart(context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Restart application")

    await context.app_actuator.restart()

    return JSONResponse()


@router.post("/stop", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def stop(context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Stop application")

    context.app_actuator.stop()

    return JSONResponse()
