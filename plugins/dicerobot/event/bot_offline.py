from app.log import logger
from app.internal.event import BotOfflineEventActive, BotOfflineEventForce, BotOfflineEventDropped
from app.internal.schedule import schedules
from plugins import EventPlugin


class BotOfflineHandler(EventPlugin):
    name = "dicerobot.bot_offline"
    display_name = "Bot 离线"
    description = "处理 Bot 离线事件"
    version = "1.0.0"

    events = [BotOfflineEventActive, BotOfflineEventForce, BotOfflineEventDropped]

    def __call__(self) -> None:
        logger.info(f"Bot offline ({self.event.__class__.__name__})")

        schedules["check_bot_status"].run()
