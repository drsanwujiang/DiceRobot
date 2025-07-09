from datetime import datetime, timedelta
from typing import Callable

from apscheduler import AsyncScheduler
from apscheduler.triggers.cron import CronTrigger
from apscheduler.triggers.interval import IntervalTrigger
from apscheduler.triggers.date import DateTrigger

from .log import logger
from .config import settings

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

    await scheduler.add_schedule(
        "dicerobot.restart", CronTrigger(year=9999), id="dicerobot.restart", paused=True
    )
    await scheduler.add_schedule(
        "dicerobot.save_config", IntervalTrigger(minutes=5), id="dicerobot.save_config"
    )
    await scheduler.add_schedule(
        "dicerobot.check_bot_status", IntervalTrigger(minutes=1), id="dicerobot.check_bot_status"
    )
    await scheduler.add_schedule(
        "dicerobot.refresh_friend_list", IntervalTrigger(minutes=5), id="dicerobot.refresh_friend_list", paused=True
    )
    await scheduler.add_schedule(
        "dicerobot.refresh_group_list", IntervalTrigger(minutes=5), id="dicerobot.refresh_group_list", paused=True
    )

    if settings.napcat.autostart:
        # Give NapCat some time to start
        await run_task("dicerobot.check_bot_status", 5)
    else:
        # Wait for initialization
        await run_task("dicerobot.check_bot_status", 1)

    await scheduler.start_in_background()

    logger.info("Scheduler initialized")


async def clean_scheduler() -> None:
    logger.info("Clean scheduler")

    await scheduler.stop()
