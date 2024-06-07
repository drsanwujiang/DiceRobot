from datetime import date

from fastapi import APIRouter, Depends, Query

from ...log import logger
from ...auth import verify_jwt_token
from ...exceptions import ResourceNotFoundError, BadRequestError
from ...manage import mirai_manager
from ...models.request.mirai import UpdateAutologinConfigRequest, CommandRequest
from .. import Response


router = APIRouter(prefix="/mirai")


@router.get("/status", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_status() -> Response:
    logger.info("Mirai manage request received: get status")

    return Response(data={
        "installed": mirai_manager.is_installed(),
        "running": mirai_manager.is_running(),
        "return_code": mirai_manager.process.returncode if mirai_manager.process is not None else None
    })


@router.get("/log", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_log(date_: date = Query(date.today(), alias="date")) -> Response:
    logger.info(f"Mirai manage request received: get log, date: {date_.isoformat()}")

    if (log := mirai_manager.get_log(date_)) is None:
        raise ResourceNotFoundError(message="Log not found")

    return Response(data={
        "log": log
    })


@router.post("/install", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def install() -> Response:
    logger.info("Mirai manage request received: install")

    if mirai_manager.is_installed():
        raise BadRequestError(message="Mirai already installed")

    mirai_manager.install()

    return Response()


@router.post("/remove", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def remove() -> Response:
    logger.info("Mirai manage request received: remove")

    if not mirai_manager.is_installed():
        raise BadRequestError(message="Mirai not installed")
    elif mirai_manager.is_running():
        raise BadRequestError(message="Mirai not stopped")

    mirai_manager.remove()

    return Response()


@router.get("/config/autologin", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_autologin_config() -> Response:
    logger.info("Mirai manage request received: get autologin config")

    return Response(data=mirai_manager.get_autologin_config())


@router.patch("/config/autologin", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_autologin_config(request: UpdateAutologinConfigRequest) -> Response:
    logger.info("Mirai manage request received: update autologin config")

    mirai_manager.set_autologin_config(request.model_dump(by_alias=True))

    return Response()


@router.post("/update", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update() -> Response:
    logger.info("Mirai manage request received: update")

    if not mirai_manager.is_installed():
        raise BadRequestError(message="Mirai not installed")
    elif mirai_manager.is_running():
        raise BadRequestError(message="Mirai not stopped")

    await mirai_manager.update()

    return Response()


@router.post("/start", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def start() -> Response:
    logger.info("Mirai manage request received: start")

    if not mirai_manager.is_installed():
        raise BadRequestError(message="Mirai not installed")
    elif mirai_manager.is_running():
        raise BadRequestError(message="Mirai already running")

    await mirai_manager.start()

    return Response()


@router.post("/stop", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def stop() -> Response:
    logger.info("Mirai manage request received: stop")

    if not mirai_manager.is_running():
        raise BadRequestError(message="Mirai not running")

    await mirai_manager.stop()

    return Response()


@router.post("/command", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def command(request: CommandRequest) -> Response:
    logger.info("Mirai manage request received: command")

    if not mirai_manager.is_installed():
        raise BadRequestError(message="Mirai not installed")
    elif not mirai_manager.is_running():
        raise BadRequestError(message="Mirai not running")

    mirai_manager.input(request.command)

    return Response()
