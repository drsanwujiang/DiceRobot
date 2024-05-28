from datetime import datetime, timedelta

from apscheduler.schedulers.background import BackgroundScheduler

from .log import logger
from .config import save_config


scheduler = BackgroundScheduler()


def init_scheduler() -> None:
    from app.task import check_bot_status, refresh_friend_list, refresh_group_list

    scheduler.add_job(save_config, id="dicerobot.save_config", trigger="interval", minutes=5)
    scheduler.add_job(check_bot_status, id="dicerobot.check_bot_status", trigger="interval", minutes=1)
    scheduler.add_job(refresh_friend_list, id="dicerobot.refresh_friend_list", trigger="interval", minutes=5).pause()
    scheduler.add_job(refresh_group_list, id="dicerobot.refresh_group_list", trigger="interval", minutes=5).pause()

    scheduler.modify_job("dicerobot.check_bot_status", next_run_time=datetime.now() + timedelta(seconds=3))
    scheduler.start()

    logger.info("Scheduler initialized")


def clean_scheduler() -> None:
    logger.info("Clean scheduler")

    scheduler.shutdown()
