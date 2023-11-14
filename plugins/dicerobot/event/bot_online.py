from app.log import logger
from app.internal.event import BotOnlineEvent, BotReloginEvent
from app.internal.schedule import schedules
from plugins import EventPlugin


class BotOnlineHandler(EventPlugin):
    name = "dicerobot.bot_online"
    description = ""

    events = [BotOnlineEvent, BotReloginEvent]

    def __call__(self) -> None:
        logger.info(f"Bot online ({self.event.__class__.__name__})")

        schedules["check_bot_status"].run()

