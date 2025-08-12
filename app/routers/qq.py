from loguru import logger
from fastapi import APIRouter, Depends

from ..auth import verify_jwt_token
from ..dependencies import AppContextDep
from ..exceptions import BadRequestError
from ..responses import JSONResponse, EventSourceResponse
from ..models.router.qq import RemoveQQRequest, UpdateQQSettingsRequest

router = APIRouter(prefix="/qq", dependencies=[Depends(verify_jwt_token, use_cache=False)])


@router.get("/status")
async def get_status(context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Get status of QQ")

    return JSONResponse(data={
        "installed": context.qq_actuator.installed,
        "version": await context.qq_actuator.get_version()
    })


@router.get("/settings")
async def get_settings(context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Get settings of QQ")

    return JSONResponse(data=context.settings.qq.model_dump())


@router.patch("/settings")
async def update_settings(context: AppContextDep, data: UpdateQQSettingsRequest) -> JSONResponse:
    logger.info("API request received: Update settings of QQ")

    context.settings.update_qq(data.model_dump(exclude_none=True))
    context.config_manager.dirty = True

    return JSONResponse()


@router.post("/update")
async def update(context: AppContextDep, ) -> EventSourceResponse:
    logger.info("API request received: Update QQ")

    if context.napcat_actuator.installed:
        raise BadRequestError(message="NapCat not removed")

    return EventSourceResponse(context.qq_actuator.create_update_stream())


@router.post("/remove")
async def remove(context: AppContextDep, data: RemoveQQRequest) -> JSONResponse:
    logger.info("API request received: Remove QQ")

    if not context.qq_actuator.installed:
        raise BadRequestError(message="QQ not installed")
    elif context.napcat_actuator.installed:
        raise BadRequestError(message="NapCat not removed")

    await context.qq_actuator.remove(**data.model_dump())

    return JSONResponse()
