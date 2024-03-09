from datetime import datetime, timedelta

from apscheduler.schedulers.background import BackgroundScheduler

from .log import logger
from .config import save_config


scheduler = BackgroundScheduler()


def init_scheduler() -> None:
    from .internal.task import check_bot_status

    logger.info("Initializing scheduler")

    scheduler.add_job(check_bot_status, id="dicerobot.check_bot_status", trigger="interval", minutes=1)
    scheduler.add_job(save_config, id="dicerobot.save_config", trigger="interval", minutes=5)

    logger.info("Scheduler initialized")


def start_scheduler() -> None:
    logger.info("Starting scheduler")

    scheduler.modify_job("dicerobot.check_bot_status", next_run_time=datetime.now() + timedelta(seconds=3))
    scheduler.start()

    logger.info("Scheduler started")


def clean_scheduler() -> None:
    logger.info("Cleaning scheduler")

    scheduler.shutdown()

    logger.info("Scheduler cleaned")
