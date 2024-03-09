from pydantic import ValidationError

from ..log import logger
from ..scheduler import scheduler
from ..config import status
from ..exceptions import DiceRobotException
from .enum import AppStatus
from .network import get_bot_list, get_bot_profile


def check_bot_status() -> None:
    logger.info("Checking bot status")

    try:
        bot_list = get_bot_list().data

        if len(bot_list) != 1:
            raise RuntimeError("No bot or too many bots online")

        bot_profile = get_bot_profile()
        status["bot"] = {"id": bot_list[0], "nickname": bot_profile.nickname}
        job = scheduler.get_job("dicerobot.check_bot_status")

        # Check job
        if job.next_run_time is None:
            job.resume()

        if status["app"] != AppStatus.RUNNING:
            status["app"] = AppStatus.RUNNING

            logger.success("DiceRobot running")
    except (DiceRobotException, ValidationError, RuntimeError):
        status["bot"] = {"id": -1, "nickname": ""}  # Clear bot status
        scheduler.pause_job("dicerobot.check_bot_status")  # Pause job

        if status["app"] != AppStatus.HOLDING:
            status["app"] = AppStatus.HOLDING

            logger.warning("DiceRobot holding")
