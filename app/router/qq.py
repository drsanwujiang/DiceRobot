from fastapi import APIRouter, Depends

from ..log import logger
from ..auth import verify_jwt_token
from ..exceptions import BadRequestError
from ..manage import qq_manager
from . import JSONResponse

router = APIRouter(prefix="/qq")


@router.get("/status", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_status() -> JSONResponse:
    logger.info("QQ manage request received: get status")

    return JSONResponse(data={
        "downloaded": qq_manager.is_downloaded(),
        "installing": qq_manager.is_installing(),
        "installed": qq_manager.is_installed(),
        "version": qq_manager.get_version()
    })


@router.post("/download", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def download() -> JSONResponse:
    logger.info("QQ manage request received: download")

    if qq_manager.is_downloaded():
        raise BadRequestError(message="QQ DEB file already downloaded")

    qq_manager.download()

    return JSONResponse()


@router.post("/install", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def install() -> JSONResponse:
    logger.info("QQ manage request received: install")

    if not qq_manager.is_downloaded():
        raise BadRequestError(message="QQ DEB file not downloaded")

    if qq_manager.is_installed():
        raise BadRequestError(message="QQ already installed")

    qq_manager.install()

    return JSONResponse()
