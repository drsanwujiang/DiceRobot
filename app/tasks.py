from typing import TYPE_CHECKING

from loguru import logger

from .exceptions import DiceRobotRuntimeException
from .enum import ApplicationStatus
from .utils import run_command

if TYPE_CHECKING:
    from .context import AppContext

STATE_TASKS = [
    "dicerobot.refresh_friend_list",
    "dicerobot.refresh_group_list"
]

__all__ = [
    "restart",
    "save_config",
    "start_napcat",
    "check_bot_status",
    "refresh_friend_list",
    "refresh_group_list"
]


async def restart() -> None:
    logger.info("Restart application")

    await run_command("systemctl restart dicerobot")


async def save_config(context: "AppContext") -> None:
    await context.config_manager.save_config()


async def start_napcat(context: "AppContext") -> None:
    if all([
        context.settings.napcat.autostart,
        context.qq_actuator.installed,
        context.napcat_actuator.installed,
        context.napcat_actuator.configured,
        not await context.napcat_actuator.check_running()
    ]):
        logger.info("Automatically start NapCat")

        await context.napcat_actuator.start()


async def check_bot_status(context: "AppContext") -> None:
    logger.info("Check bot status")

    try:
        data = (await context.network_manager.napcat.get_login_info()).data

        # Update status
        context.status.bot.id = data.user_id
        context.status.bot.nickname = data.nickname

        if context.status.app != ApplicationStatus.RUNNING:
            context.status.app = ApplicationStatus.RUNNING

            logger.success("Application status changed: Running")

            await refresh_friend_list(context)
            await refresh_group_list(context)

            # Resume state jobs
            for schedule in STATE_TASKS:
                if (await context.task_manager.scheduler.get_schedule(schedule)).paused:
                    await context.task_manager.scheduler.unpause_schedule(schedule, resume_from="now")
    except (DiceRobotRuntimeException, ValueError, RuntimeError):
        # Clear status
        context.status.bot.id = -1
        context.status.bot.nickname = ""
        context.status.bot.friends = []
        context.status.bot.groups = []

        if context.status.app != ApplicationStatus.HOLDING:
            context.status.app = ApplicationStatus.HOLDING

            logger.warning("Application status changed: Holding")

            # Pause state jobs
            for schedule in STATE_TASKS:
                await context.task_manager.scheduler.pause_schedule(schedule)


async def refresh_friend_list(context: "AppContext") -> None:
    logger.info("Refresh friend list")

    try:
        friends = (await context.network_manager.napcat.get_friend_list()).data
        context.status.bot.friends = [friend.user_id for friend in friends]
    except (DiceRobotRuntimeException, ValueError):
        context.status.bot.friends = []

        logger.error("Failed to refresh friend list")


async def refresh_group_list(context: "AppContext") -> None:
    logger.info("Refresh group list")

    try:
        groups = (await context.network_manager.napcat.get_group_list()).data
        context.status.bot.groups = [group.group_id for group in groups]
    except (DiceRobotRuntimeException, ValueError):
        context.status.bot.groups = []

        logger.error("Failed to refresh group list")
