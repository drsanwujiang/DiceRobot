from ..log import logger
from ..scheduler import scheduler
from ..config import status
from ..exceptions import DiceRobotException
from .enum import AppStatus
from .network import get_bot_list, get_bot_profile, get_friend_list, get_group_list

state_tasks = [
    "dicerobot.check_bot_status",
    "dicerobot.refresh_friend_list",
    "dicerobot.refresh_group_list"
]


def check_bot_status() -> None:
    logger.info("Check bot status")

    try:
        bot_list = get_bot_list().data

        if len(bot_list) != 1:
            raise RuntimeError("No bot or too many bots online")

        status["bot"] = {
            "id": bot_list[0],
            "nickname": get_bot_profile().nickname
        }

        if status["app"] != AppStatus.RUNNING:
            status["app"] = AppStatus.RUNNING

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
        status["bot"] = {
            "id": -1,
            "nickname": ""
        }
        status["friends"] = []
        status["groups"] = []

        if status["app"] != AppStatus.HOLDING:
            status["app"] = AppStatus.HOLDING

            logger.warning("Status changed: Holding")

            # Pause state jobs
            for job in state_tasks:
                scheduler.pause_job(job)


def refresh_friend_list() -> None:
    logger.info("Refresh friend list")

    try:
        friend_list = get_friend_list().data
        status["friends"] = [friend.id for friend in friend_list]
    except (DiceRobotException, ValueError):
        status["friends"] = []

        logger.error("Failed to refresh friend list")


def refresh_group_list() -> None:
    logger.info("Refresh group list")

    try:
        group_list = get_group_list().data
        status["groups"] = [group.id for group in group_list]
    except (DiceRobotException, ValueError):
        status["groups"] = []

        logger.error("Failed to refresh group list")
