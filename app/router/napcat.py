from loguru import logger
from fastapi import APIRouter, Depends

from ..auth import verify_jwt_token
from ..config import settings
from ..exceptions import ResourceNotFoundError, BadRequestError
from ..manage import qq_manager, napcat_manager
from ..responses import JSONResponse, EventSourceResponse
from ..models.router.napcat import UpdateNapCatSettingsRequest

router = APIRouter(prefix="/napcat")


@router.get("/status", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_status() -> JSONResponse:
    logger.info("NapCat management request received: get status")

    return JSONResponse(data={
        "installed": napcat_manager.installed(),
        "configured": napcat_manager.configured(),
        "running": await napcat_manager.running(),
        "version": await napcat_manager.get_version()
    })


@router.get("/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_settings() -> JSONResponse:
    logger.info("NapCat management request received: get settings")

    return JSONResponse(data=settings.napcat.model_dump())


@router.patch("/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_settings(data: UpdateNapCatSettingsRequest) -> JSONResponse:
    logger.info("NapCat management request received: update settings")

    settings.update_napcat(data.model_dump(exclude_none=True))

    return JSONResponse()


@router.get("/logs", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_logs() -> EventSourceResponse:
    logger.info("NapCat management request received: get logs")

    if not await napcat_manager.running():
        raise BadRequestError(message="NapCat not running")
    elif not (filename := napcat_manager.get_log_file()):
        raise ResourceNotFoundError(message="Logs not found")

    return EventSourceResponse(napcat_manager.create_logs_stream(filename))


@router.post("/update", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update() -> EventSourceResponse:
    logger.info("NapCat management request received: update")

    if not qq_manager.installed():
        raise BadRequestError(message="QQ not installed")
    elif await napcat_manager.running():
        raise BadRequestError(message="NapCat not stopped")

    return EventSourceResponse(napcat_manager.create_update_stream())


@router.post("/remove", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def remove() -> JSONResponse:
    logger.info("NapCat management request received: remove")

    if not napcat_manager.installed():
        raise BadRequestError(message="NapCat not installed")
    elif await napcat_manager.running():
        raise BadRequestError(message="NapCat not stopped")

    await napcat_manager.remove()

    return JSONResponse()


@router.post("/start", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def start() -> JSONResponse:
    logger.info("NapCat management request received: start")

    if not qq_manager.installed():
        raise BadRequestError(message="QQ not installed")
    elif not napcat_manager.installed():
        raise BadRequestError(message="NapCat not installed")
    elif not napcat_manager.configured():
        raise BadRequestError(message="NapCat not configured")
    elif await napcat_manager.running():
        raise BadRequestError(message="NapCat already running")

    await napcat_manager.start()

    return JSONResponse()


@router.post("/stop", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def stop() -> JSONResponse:
    logger.info("NapCat management request received: stop")

    if not await napcat_manager.running():
        raise BadRequestError(message="NapCat not running")

    await napcat_manager.stop()

    return JSONResponse()
