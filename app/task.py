from loguru import logger

from .schedule import scheduler
from .config import status, save_config as save_config_
from .exceptions import DiceRobotRuntimeException
from .enum import ApplicationStatus
from .network.napcat import get_login_info, get_friend_list, get_group_list
from .utils import run_command

state_tasks = [
    "dicerobot.refresh_friend_list",
    "dicerobot.refresh_group_list"
]


async def restart() -> None:
    logger.info("Restart application")

    await run_command("systemctl restart dicerobot")


async def save_config() -> None:
    save_config_()


async def check_bot_status() -> None:
    logger.info("Check bot status")

    try:
        data = (await get_login_info()).data

        # Update status
        status.bot.id = data.user_id
        status.bot.nickname = data.nickname

        if status.app != ApplicationStatus.RUNNING:
            status.app = ApplicationStatus.RUNNING

            logger.success("Status changed: Running")

            await refresh_friend_list()
            await refresh_group_list()

            # Resume state jobs
            for schedule in state_tasks:
                if (await scheduler.get_schedule(schedule)).paused:
                    await scheduler.unpause_schedule(schedule, resume_from="now")
    except (DiceRobotRuntimeException, ValueError, RuntimeError):
        # Clear status
        status.bot.id = -1
        status.bot.nickname = ""
        status.bot.friends = []
        status.bot.groups = []

        if status.app != ApplicationStatus.HOLDING:
            status.app = ApplicationStatus.HOLDING

            logger.warning("Status changed: Holding")

            # Pause state jobs
            for schedule in state_tasks:
                await scheduler.pause_schedule(schedule)


async def refresh_friend_list() -> None:
    logger.info("Refresh friend list")

    try:
        friends = (await get_friend_list()).data
        status.bot.friends = [friend.user_id for friend in friends]
    except (DiceRobotRuntimeException, ValueError):
        status.bot.friends = []

        logger.error("Failed to refresh friend list")


async def refresh_group_list() -> None:
    logger.info("Refresh group list")

    try:
        groups = (await get_group_list()).data
        status.bot.groups = [group.group_id for group in groups]
    except (DiceRobotRuntimeException, ValueError):
        status.bot.groups = []

        logger.error("Failed to refresh group list")
