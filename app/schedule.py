from datetime import datetime, timedelta, timezone
from typing import Callable

from loguru import logger
from apscheduler import AsyncScheduler
from apscheduler.triggers.cron import CronTrigger
from apscheduler.triggers.interval import IntervalTrigger
from apscheduler.triggers.date import DateTrigger

from .config import settings

__all__ = [
    "scheduler",
    "init_scheduler",
    "clean_scheduler"
]

tasks: dict[str, Callable] = {}
scheduler: AsyncScheduler | None = None


async def run_task(task_id: str, delay: int = 0) -> None:
    if task_id not in tasks:
        raise ValueError(f"Task ID '{task_id}' not exists")

    await scheduler.add_schedule(task_id, DateTrigger(datetime.now() + timedelta(seconds=delay)))


async def init_scheduler(scheduler_: AsyncScheduler) -> None:
    global scheduler
    scheduler = scheduler_

    from .task import restart, save_config, check_bot_status, refresh_friend_list, refresh_group_list

    global tasks
    tasks = {
        "dicerobot.restart": restart,
        "dicerobot.save_config": save_config,
        "dicerobot.check_bot_status": check_bot_status,
        "dicerobot.refresh_friend_list": refresh_friend_list,
        "dicerobot.refresh_group_list": refresh_group_list
    }

    for task, func in tasks.items():
        await scheduler.configure_task(task, func=func)

    # Add schedules but not start them yet
    await scheduler.add_schedule(
        "dicerobot.restart", CronTrigger(year=9999), id="dicerobot.restart", paused=True
    )
    await scheduler.add_schedule(
        "dicerobot.save_config", IntervalTrigger(minutes=5), id="dicerobot.save_config", paused=True
    )
    await scheduler.add_schedule(
        "dicerobot.check_bot_status", IntervalTrigger(minutes=1), id="dicerobot.check_bot_status", paused=True
    )
    await scheduler.add_schedule(
        "dicerobot.refresh_friend_list", IntervalTrigger(minutes=5), id="dicerobot.refresh_friend_list", paused=True
    )
    await scheduler.add_schedule(
        "dicerobot.refresh_group_list", IntervalTrigger(minutes=5), id="dicerobot.refresh_group_list", paused=True
    )

    # Unpause schedules, make sure they will run at the next fire time
    await scheduler.unpause_schedule(
        "dicerobot.save_config", resume_from=datetime.now(timezone.utc) + timedelta(seconds=5)
    )
    await scheduler.unpause_schedule(
        "dicerobot.check_bot_status", resume_from=datetime.now(timezone.utc) + timedelta(seconds=5)
    )

    if settings.napcat.autostart:
        # Give NapCat some time to start
        await run_task("dicerobot.check_bot_status", 5)
    else:
        # Wait for initialization
        await run_task("dicerobot.check_bot_status", 1)

    await scheduler.start_in_background()

    logger.debug("Scheduler initialized")


async def clean_scheduler() -> None:
    logger.debug("Clean scheduler")

    await scheduler.stop()
