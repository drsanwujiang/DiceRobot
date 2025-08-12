from loguru import logger

from app.models.report.request import FriendRequest
from ... import EventPlugin


class FriendRequestHandler(EventPlugin):
    name = "dicerobot.friend_request"
    display_name = "好友申请"
    description = "处理好友申请"
    version = "1.2.0"
    default_plugin_settings = {
        "auto_approve": True
    }
    events = [
        FriendRequest
    ]

    async def __call__(self) -> None:
        logger.success(f"Friend request from {self.event.user_id} received")

        if self.plugin_settings["auto_approve"]:
            await self.context.network_manager.napcat.set_friend_add_request(self.event.flag, True)
            logger.success(f"Friend request from {self.event.user_id} automatically approved")
