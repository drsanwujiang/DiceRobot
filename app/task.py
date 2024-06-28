from .log import logger
from .schedule import scheduler
from .config import status
from .exceptions import DiceRobotException
from .enum import ApplicationStatus
from .network.napcat import get_login_info, get_friend_list, get_group_list

state_tasks = [
    "dicerobot.refresh_friend_list",
    "dicerobot.refresh_group_list"
]


def check_bot_status() -> None:
    logger.info("Check bot status")

    try:
        data = get_login_info().data

        # Update status
        status.bot.id = data.user_id
        status.bot.nickname = data.nickname

        if status.app != ApplicationStatus.RUNNING:
            status.app = ApplicationStatus.RUNNING

            logger.success("Status changed: Running")

            refresh_friend_list()
            refresh_group_list()

            # Resume state jobs
            for _job in state_tasks:
                job = scheduler.get_job(_job)

                if job.next_run_time is None:
                    job.resume()
    except (DiceRobotException, ValueError, RuntimeError):
        # Clear status
        status.bot.id = -1
        status.bot.nickname = ""
        status.bot.friends = []
        status.bot.groups = []

        if status.app != ApplicationStatus.HOLDING:
            status.app = ApplicationStatus.HOLDING

            logger.warning("Status changed: Holding")

            # Pause state jobs
            for job in state_tasks:
                scheduler.pause_job(job)


def refresh_friend_list() -> None:
    logger.info("Refresh friend list")

    try:
        friends = get_friend_list().data
        status.bot.friends = [friend.user_id for friend in friends]
    except (DiceRobotException, ValueError):
        status.bot.friends = []

        logger.error("Failed to refresh friend list")


def refresh_group_list() -> None:
    logger.info("Refresh group list")

    try:
        groups = get_group_list().data
        status.bot.groups = [group.group_id for group in groups]
    except (DiceRobotException, ValueError):
        status.bot.groups = []

        logger.error("Failed to refresh group list")
