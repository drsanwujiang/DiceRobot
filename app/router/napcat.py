import asyncio

from fastapi import APIRouter, Depends, Request

from ..log import logger
from ..auth import verify_jwt_token
from ..config import settings
from ..exceptions import ResourceNotFoundError, BadRequestError
from ..manage import qq_manager, napcat_manager
from ..enum import UpdateStatus
from ..models.panel.napcat import UpdateNapCatSettingsRequest
from . import JSONResponse, StreamingResponse

router = APIRouter(prefix="/napcat")


@router.get("/status", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_status() -> JSONResponse:
    logger.info("NapCat management request received: get status")

    return JSONResponse(data={
        "installed": napcat_manager.installed(),
        "configured": napcat_manager.configured(),
        "running": napcat_manager.running(),
        "version": napcat_manager.get_version()
    })


@router.post("/update", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def download(request: Request) -> StreamingResponse:
    logger.info("NapCat management request received: update")

    task = asyncio.create_task(napcat_manager.update())

    async def content_generator():
        while await request.is_disconnected():
            yield {"status": str(napcat_manager.update_status)}

            if napcat_manager.update_status in [UpdateStatus.COMPLETED, UpdateStatus.FAILED]:
                napcat_manager.update_status = UpdateStatus.NONE
                break
            elif task.done() and napcat_manager.update_status != UpdateStatus.COMPLETED:
                napcat_manager.update_status = UpdateStatus.FAILED
                continue

            await asyncio.sleep(1)

    return StreamingResponse(content_generator())


@router.post("/remove", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def remove() -> JSONResponse:
    logger.info("NapCat management request received: remove")

    if not napcat_manager.installed():
        raise BadRequestError(message="NapCat not installed")
    elif napcat_manager.running():
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
    elif napcat_manager.running():
        raise BadRequestError(message="NapCat already running")

    await napcat_manager.start()

    return JSONResponse()


@router.post("/stop", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def stop() -> JSONResponse:
    logger.info("NapCat management request received: stop")

    if not napcat_manager.is_running():
        raise BadRequestError(message="NapCat not running")

    await napcat_manager.stop()

    return JSONResponse()


@router.get("/logs", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_logs() -> JSONResponse:
    logger.info("NapCat management request received: get logs")

    if not napcat_manager.is_running():
        raise BadRequestError(message="NapCat not running")
    elif not (logs := napcat_manager.get_logs()):
        raise ResourceNotFoundError(message="Logs not found")

    return JSONResponse(data=logs)


@router.get("/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_settings() -> JSONResponse:
    logger.info("NapCat management request received: get settings")

    return JSONResponse(data=settings.napcat.model_dump())


@router.patch("/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_napcat_settings(data: UpdateNapCatSettingsRequest) -> JSONResponse:
    logger.info("NapCat management request received: update settings")

    settings.update_napcat(data.model_dump(exclude_none=True))

    return JSONResponse()
