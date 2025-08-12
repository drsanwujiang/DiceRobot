from fastapi import FastAPI

from .webhook import router as webhook
from .admin import router as admin
from .qq import router as qq
from .napcat import router as napcat


def init_router(app: FastAPI) -> None:
    app.include_router(webhook)
    app.include_router(admin)
    app.include_router(qq)
    app.include_router(napcat)
