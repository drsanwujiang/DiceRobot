import asyncio
from collections.abc import AsyncGenerator

from fastapi import APIRouter, Depends
from sse_starlette import ServerSentEvent

from ..log import logger
from ..auth import verify_jwt_token
from ..exceptions import BadRequestError
from ..manage import qq_manager, napcat_manager
from ..utils import generate_sse
from ..enum import UpdateStatus
from ..models.router.qq import RemoveQQRequest
from . import JSONResponse, EventSourceResponse

router = APIRouter(prefix="/qq")


@router.get("/status", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def get_status() -> JSONResponse:
    logger.info("QQ management request received: get status")

    return JSONResponse(data={
        "installed": qq_manager.installed(),
        "version": qq_manager.get_version()
    })


@router.post("/update", dependencies=[Depends(verify_jwt_token, use_cache=False)])
async def update() -> EventSourceResponse:
    logger.info("QQ management request received: update")

    task = asyncio.create_task(qq_manager.update())

    async def content_generator() -> AsyncGenerator[ServerSentEvent]:
        while True:
            yield generate_sse({"status": str(qq_manager.update_status)})

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
    logger.info("NapCat management request received: remove")

    if not qq_manager.installed():
        raise BadRequestError(message="QQ not installed")
    elif napcat_manager.installed():
        raise BadRequestError(message="NapCat not removed")

    await qq_manager.remove(**data.model_dump())

    return JSONResponse()
