from fastapi import APIRouter, Depends

from ..log import logger
from ..auth import verify_jwt_token
from ..config import settings
from ..exceptions import ResourceNotFoundError, BadRequestError
from ..manage import qq_manager, napcat_manager
from ..models.panel.napcat import UpdateNapCatSettingsRequest
from . import JSONResponse

router = APIRouter(prefix="/napcat")


@router.get("/status", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_status() -> JSONResponse:
    logger.info("NapCat manage request received: get status")

    return JSONResponse(data={
        "downloading": napcat_manager.is_downloading(),
        "downloaded": napcat_manager.is_downloaded(),
        "installed": napcat_manager.is_installed(),
        "configured": napcat_manager.is_configured(),
        "running": napcat_manager.is_running(),
        "version": napcat_manager.get_version()
    })


@router.post("/download", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def download() -> JSONResponse:
    logger.info("NapCat manage request received: download")

    if napcat_manager.is_downloading():
        raise BadRequestError(message="NapCat ZIP file is downloading")
    elif napcat_manager.is_downloaded():
        raise BadRequestError(message="NapCat ZIP file already downloaded")

    napcat_manager.download()

    return JSONResponse()


@router.post("/install", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def install() -> JSONResponse:
    logger.info("NapCat manage request received: install")

    if not qq_manager.is_installed():
        raise BadRequestError(message="QQ not installed")
    elif not napcat_manager.is_downloaded():
        raise BadRequestError(message="NapCat ZIP file not downloaded")
    elif napcat_manager.is_installed():
        raise BadRequestError(message="NapCat already installed")

    napcat_manager.install()

    return JSONResponse()


@router.post("/remove", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def remove() -> JSONResponse:
    logger.info("NapCat manage request received: remove")

    if not napcat_manager.is_installed():
        raise BadRequestError(message="NapCat not installed")
    elif napcat_manager.is_running():
        raise BadRequestError(message="NapCat not stopped")

    napcat_manager.remove()

    return JSONResponse()


@router.post("/start", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def start() -> JSONResponse:
    logger.info("NapCat manage request received: start")

    if not qq_manager.is_installed():
        raise BadRequestError(message="QQ not installed")
    elif not napcat_manager.is_installed():
        raise BadRequestError(message="NapCat not installed")
    elif not napcat_manager.is_configured():
        raise BadRequestError(message="NapCat not configured")
    elif napcat_manager.is_running():
        raise BadRequestError(message="NapCat already running")

    napcat_manager.start()

    return JSONResponse()


@router.post("/stop", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def stop() -> JSONResponse:
    logger.info("NapCat manage request received: stop")

    if not napcat_manager.is_running():
        raise BadRequestError(message="NapCat not running")

    napcat_manager.stop()

    return JSONResponse()


@router.get("/logs", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_logs() -> JSONResponse:
    logger.info("NapCat manage request received: get logs")

    if not napcat_manager.is_running():
        raise BadRequestError(message="NapCat not running")
    elif not (logs := napcat_manager.get_logs()):
        raise ResourceNotFoundError(message="Logs not found")

    return JSONResponse(data=logs)


@router.get("/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_settings() -> JSONResponse:
    logger.info("NapCat manage request received: get settings")

    return JSONResponse(data=settings.napcat.model_dump())


@router.patch("/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_napcat_settings(data: UpdateNapCatSettingsRequest) -> JSONResponse:
    logger.info("NapCat manage request received: update settings")

    settings.update_napcat(data.model_dump(exclude_none=True))

    return JSONResponse()
