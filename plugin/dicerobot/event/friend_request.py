from plugin import EventPlugin
from app.log import logger
from app.models.event import NewFriendRequestEvent
from app.models.network.mirai import RespondNewFriendRequestEventRequest
from app.network.mirai import respond_new_friend_request_event


class FriendRequestHandler(EventPlugin):
    name = "dicerobot.friend_request"
    display_name = "好友申请"
    description = "处理好友申请"
    version = "1.0.0"

    default_plugin_settings = {
        "auto_approve": True
    }

    events = NewFriendRequestEvent

    def __call__(self) -> None:
        logger.success(f"Friend request from {self.event.from_id} received")

        if self.get_plugin_setting(key="auto_approve"):
            respond_new_friend_request_event(RespondNewFriendRequestEventRequest(
                event_id=self.event.event_id,
                from_id=self.event.from_id,
                group_id=self.event.group_id,
                operate=0,
                message=""
            ))

            logger.success(f"Friend request from {self.event.from_id} automatically approved")
