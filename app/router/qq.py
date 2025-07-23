import asyncio
from collections.abc import AsyncGenerator

from loguru import logger
from fastapi import APIRouter, Depends
from sse_starlette import JSONServerSentEvent

from ..auth import verify_jwt_token
from ..config import settings
from ..exceptions import BadRequestError
from ..manage import qq_manager, napcat_manager
from ..responses import JSONResponse, EventSourceResponse
from ..enum import UpdateStatus
from ..models.router.qq import RemoveQQRequest, UpdateQQSettingsRequest

router = APIRouter(prefix="/qq")


@router.get("/status", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_status() -> JSONResponse:
    logger.info("QQ management request received: get status")

    return JSONResponse(data={
        "installed": qq_manager.installed(),
        "version": await qq_manager.get_version()
    })


@router.post("/update", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update() -> EventSourceResponse:
    logger.info("QQ management request received: update")

    if napcat_manager.installed():
        raise BadRequestError(message="NapCat not removed")

    task = asyncio.create_task(qq_manager.update())

    async def content_generator() -> AsyncGenerator[JSONServerSentEvent]:
        while True:
            yield JSONServerSentEvent({"status": qq_manager.update_status.value})

            if qq_manager.update_status in [UpdateStatus.COMPLETED, UpdateStatus.FAILED]:
                qq_manager.update_status = UpdateStatus.NONE
                break
            elif task.done() and qq_manager.update_status != UpdateStatus.COMPLETED:
                qq_manager.update_status = UpdateStatus.FAILED
                continue

            await asyncio.sleep(1)

    return EventSourceResponse(content_generator())


@router.post("/remove", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def remove(data: RemoveQQRequest) -> JSONResponse:
    logger.info("QQ management request received: remove")

    if not qq_manager.installed():
        raise BadRequestError(message="QQ not installed")
    elif napcat_manager.installed():
        raise BadRequestError(message="NapCat not removed")

    await qq_manager.remove(**data.model_dump())

    return JSONResponse()


@router.get("/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_settings() -> JSONResponse:
    logger.info("QQ management request received: get settings")

    return JSONResponse(data=settings.qq.model_dump())


@router.patch("/settings", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update_settings(data: UpdateQQSettingsRequest) -> JSONResponse:
    logger.info("QQ management request received: update settings")

    settings.update_qq(data.model_dump(exclude_none=True))

    return JSONResponse()
