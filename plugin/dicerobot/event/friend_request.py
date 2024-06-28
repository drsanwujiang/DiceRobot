from plugin import EventPlugin
from app.log import logger
from app.models.report.request import FriendAddRequest
from app.network.napcat import set_friend_add_request


class FriendRequestHandler(EventPlugin):
    name = "dicerobot.friend_request"
    display_name = "好友申请"
    description = "处理好友申请"
    version = "1.1.0"

    default_plugin_settings = {
        "auto_approve": True
    }

    events = [
        FriendAddRequest
    ]

    def __call__(self) -> None:
        logger.success(f"Friend request from {self.event.user_id} received")

        if self.plugin_settings["auto_approve"]:
            set_friend_add_request(self.event.flag, True)

            logger.success(f"Friend request from {self.event.user_id} automatically approved")
