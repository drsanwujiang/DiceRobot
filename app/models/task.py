from typing import Callable, Coroutine, Iterable, Any
from dataclasses import dataclass

from apscheduler.abc import Trigger


@dataclass
class ScheduledTask:
    id: str
    func: Callable[..., Coroutine]
    trigger: Trigger
    paused_on_init: bool = True
    args: Iterable[Any] = None
