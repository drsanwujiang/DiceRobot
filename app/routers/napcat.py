from loguru import logger
from fastapi import APIRouter, Depends

from ..auth import verify_jwt_token
from ..dependencies import AppContextDep
from ..exceptions import ResourceNotFoundError, BadRequestError
from ..responses import JSONResponse, EventSourceResponse
from ..models.router.napcat import UpdateNapCatSettingsRequest

router = APIRouter(prefix="/napcat", dependencies=[Depends(verify_jwt_token, use_cache=False)])


@router.get("/status")
async def get_status(context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Get status of NapCat")

    return JSONResponse(data={
        "installed": context.napcat_actuator.installed,
        "configured": context.napcat_actuator.configured,
        "running": await context.napcat_actuator.check_running(),
        "version": await context.napcat_actuator.get_version()
    })


@router.get("/settings")
async def get_settings(context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Get settings of NapCat")

    return JSONResponse(data=context.settings.napcat.model_dump())


@router.patch("/settings")
async def update_settings(data: UpdateNapCatSettingsRequest, context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Update settings of NapCat")

    context.settings.update_napcat(data.model_dump(exclude_none=True))
    context.config_manager.dirty = True

    return JSONResponse()


@router.get("/logs")
async def get_logs(context: AppContextDep) -> EventSourceResponse:
    logger.info("API request received: Get logs of NapCat")

    if not await context.napcat_actuator.check_running():
        raise BadRequestError(message="NapCat not running")
    elif not (filename := context.napcat_actuator.get_log_file()):
        raise ResourceNotFoundError(message="Logs not found")

    return EventSourceResponse(context.napcat_actuator.create_logs_stream(filename))


@router.post("/update")
async def update(context: AppContextDep) -> EventSourceResponse:
    logger.info("API request received: Update NapCat")

    if not context.qq_actuator.installed:
        raise BadRequestError(message="QQ not installed")
    elif await context.napcat_actuator.check_running():
        raise BadRequestError(message="NapCat not stopped")

    return EventSourceResponse(context.napcat_actuator.create_update_stream())


@router.post("/remove")
async def remove(context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Remove NapCat")

    if not context.napcat_actuator.installed:
        raise BadRequestError(message="NapCat not installed")
    elif await context.napcat_actuator.check_running():
        raise BadRequestError(message="NapCat not stopped")

    await context.napcat_actuator.remove()

    return JSONResponse()


@router.post("/start")
async def start(context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Start NapCat")

    if not context.qq_actuator.installed:
        raise BadRequestError(message="QQ not installed")
    elif not context.napcat_actuator.installed:
        raise BadRequestError(message="NapCat not installed")
    elif not context.napcat_actuator.configured:
        raise BadRequestError(message="NapCat not configured")
    elif await context.napcat_actuator.check_running():
        raise BadRequestError(message="NapCat already running")

    await context.napcat_actuator.start()

    return JSONResponse()


@router.post("/stop")
async def stop(context: AppContextDep) -> JSONResponse:
    logger.info("API request received: Stop NapCat")

    if not await context.napcat_actuator.check_running():
        raise BadRequestError(message="NapCat not running")

    await context.napcat_actuator.stop()

    return JSONResponse()
