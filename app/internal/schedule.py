import threading
import time
from typing import Callable

import schedule
from pydantic import ValidationError

from ..log import logger
from ..config import status, save_config
from ..exceptions import DiceRobotException
from .enum import AppStatus
from .network import get_bot_list, get_bot_profile


schedules: dict[str, schedule.Job] = {}
stop_schedule: threading.Event


def schedule_once(func: Callable, delay: int = 0, *args, **kwargs) -> None:
    class ScheduleThread(threading.Thread):
        @classmethod
        def run(cls):
            time.sleep(delay)
            func(*args, **kwargs)

    schedule_thread = ScheduleThread()
    schedule_thread.start()


def schedule_continuously() -> threading.Event:
    stop_flag = threading.Event()

    class ScheduleThread(threading.Thread):
        @classmethod
        def run(cls):
            while not stop_flag.is_set():
                schedule.run_pending()
                time.sleep(1)

    schedule_thread = ScheduleThread()
    schedule_thread.start()

    return stop_flag


def check_bot_status() -> None:
    logger.info("Checking bot status")

    try:
        bot_list = get_bot_list().data

        if len(bot_list) != 1:
            raise RuntimeError("No bot or too many bots online")

        bot_profile = get_bot_profile()
        status["bot"] = {"id": bot_list[0], "nickname": bot_profile.nickname}

        if status["app"] <= AppStatus.HOLDING:
            status["app"] = AppStatus.RUNNING

            logger.success("DiceRobot running")
    except (DiceRobotException, ValidationError):
        status["bot"] = {"id": -1, "nickname": ""}

        if status["app"] >= AppStatus.RUNNING:
            status["app"] = AppStatus.HOLDING

            logger.error("DiceRobot holding")


def init_schedule():
    logger.info("Initializing schedule")

    schedules["check_bot_status"] = schedule.every(1).minutes.do(check_bot_status)
    schedules["save_config"] = schedule.every(5).minutes.do(save_config)

    schedule_once(check_bot_status, delay=1)

    global stop_schedule
    stop_schedule = schedule_continuously()

    logger.info("Schedule initialized")


def clean_schedule():
    logger.info("Cleaning schedule")

    stop_schedule.set()

    logger.info("Schedule cleaned")
