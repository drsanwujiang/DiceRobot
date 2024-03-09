from plugins import EventPlugin
from app.log import logger
from app.internal.event import BotOnlineEvent, BotReloginEvent
from app.internal.task import check_bot_status


class BotOnlineHandler(EventPlugin):
    name = "dicerobot.bot_online"
    display_name = "Bot 上线"
    description = "处理 Bot 上线事件"
    version = "1.0.0"

    events = [BotOnlineEvent, BotReloginEvent]

    def __call__(self) -> None:
        logger.info(f"Bot online ({self.event.__class__.__name__})")

        check_bot_status()

