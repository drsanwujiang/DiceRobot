from typing import Callable
from threading import Thread, Event
import time

import schedule
from pydantic import ValidationError

from ..log import logger
from ..config import status, save_config
from ..exceptions import DiceRobotException
from .enum import AppStatus
from .network import get_bot_list, get_bot_profile


schedules: dict[str, schedule.Job | None] = {}
stop_schedule: Event


def init_schedule():
    logger.info("Initializing schedule")

    global stop_schedule
    stop_schedule = schedule_continuously()

    # Check bot status every minute
    schedules["check_bot_status"] = schedule.every(1).minutes.do(check_bot_status)
    # Save config every 5 minutes
    schedules["save_config"] = schedule.every(5).minutes.do(save_config)
    # Check bot status immediately
    schedule_once(check_bot_status, delay=1)

    logger.info("Schedule initialized")


def clean_schedule():
    logger.info("Cleaning schedule")

    stop_schedule.set()

    logger.info("Schedule cleaned")


def schedule_continuously() -> Event:
    class ScheduleThread(Thread):
        stop_flag = Event()

        @classmethod
        def run(cls):
            while not cls.stop_flag.is_set():
                schedule.run_pending()
                time.sleep(1)

    schedule_thread = ScheduleThread()
    schedule_thread.start()

    return ScheduleThread.stop_flag


def schedule_once(func: Callable, delay: int = 0, *args, **kwargs) -> None:
    class ScheduleThread(Thread):
        @classmethod
        def run(cls):
            time.sleep(delay)
            func(*args, **kwargs)

    schedule_thread = ScheduleThread()
    schedule_thread.start()


def check_bot_status() -> None:
    logger.info("Checking bot status")

    try:
        bot_list = get_bot_list().data

        if len(bot_list) != 1:
            raise RuntimeError("No bot or too many bots online")

        bot_profile = get_bot_profile()
        status["bot"] = {"id": bot_list[0], "nickname": bot_profile.nickname}

        # Check schedule
        if schedules["check_bot_status"] is None:
            schedules["check_bot_status"] = schedule.every(1).minutes.do(check_bot_status)

        if status["app"] != AppStatus.RUNNING:
            status["app"] = AppStatus.RUNNING

            logger.success("DiceRobot running")
    except (DiceRobotException, ValidationError, RuntimeError):
        # Clear bot status
        status["bot"] = {"id": -1, "nickname": ""}
        # Cancel schedule
        schedule.cancel_job(schedules["check_bot_status"])
        schedules["check_bot_status"] = None

        if status["app"] != AppStatus.HOLDING:
            status["app"] = AppStatus.HOLDING

            logger.warning("DiceRobot holding")
