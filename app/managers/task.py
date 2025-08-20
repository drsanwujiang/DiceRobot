from typing import TYPE_CHECKING

from loguru import logger
from apscheduler.triggers.cron import CronTrigger
from apscheduler.triggers.interval import IntervalTrigger
from apscheduler.triggers.date import DateTrigger
import arrow

from ..models.task import ScheduledTask
from ..tasks import restart, save_config, start_napcat, check_bot_status, refresh_friend_list, refresh_group_list
from . import Manager

if TYPE_CHECKING:
    from ..context import AppContext

__all__ = [
    "TaskManager"
]


class TaskManager(Manager):
    def __init__(self, context: "AppContext") -> None:
        super().__init__(context)

        self.scheduler = context.scheduler
        self._tasks: dict[str, ScheduledTask] = {}

    async def initialize(self) -> None:
        self._tasks = {
            "dicerobot.restart": ScheduledTask("dicerobot.restart", restart, CronTrigger(year=9999)),
            "dicerobot.save_config": ScheduledTask("dicerobot.save_config", save_config, IntervalTrigger(minutes=5), args=(self.context,)),
            "dicerobot.start_napcat": ScheduledTask("dicerobot.start_napcat", start_napcat, CronTrigger(year=9999), args=(self.context,)),
            "dicerobot.check_bot_status": ScheduledTask("dicerobot.check_bot_status", check_bot_status, trigger=IntervalTrigger(minutes=1), args=(self.context,)),
            "dicerobot.refresh_friend_list": ScheduledTask("dicerobot.refresh_friend_list", refresh_friend_list, IntervalTrigger(minutes=5), args=(self.context,)),
            "dicerobot.refresh_group_list": ScheduledTask("dicerobot.refresh_group_list", refresh_group_list, IntervalTrigger(minutes=5), args=(self.context,))
        }

        for task in self._tasks.values():
            await self.scheduler.configure_task(task.id, func=task.func)
            await self.scheduler.add_schedule(task.id, task.trigger, id=task.id, args=task.args, paused=task.paused_on_init)

        await self.scheduler.unpause_schedule("dicerobot.save_config")
        await self.scheduler.unpause_schedule("dicerobot.check_bot_status")

        if self.context.settings.napcat.autostart:
            await self.run_task_later("dicerobot.start_napcat", 1)
            await self.run_task_later("dicerobot.check_bot_status", 5)
        else:
            await self.run_task_later("dicerobot.check_bot_status", 1)

        await self.scheduler.start_in_background()
        logger.debug("Schedule manager initialized")

    async def cleanup(self):
        logger.debug("Clean schedule manager")
        await self.scheduler.stop()

    async def run_task_later(self, task_id: str, delay: int = 0):
        if task_id not in self._tasks:
            raise ValueError(f"Task ID \"{task_id}\" not registered")

        task = self._tasks[task_id]
        await self.scheduler.add_schedule(task.id, DateTrigger(arrow.now().shift(seconds=delay).datetime), args=task.args)
