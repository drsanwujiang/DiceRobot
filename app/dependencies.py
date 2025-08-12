from typing import Annotated

from fastapi import Request, Depends

from .context import AppContext

__all__ = [
    "AppContextDep"
]


def get_app_context(request: Request) -> AppContext:
    return request.app.state.context


AppContextDep = Annotated[AppContext, Depends(get_app_context)]
