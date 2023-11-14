from app.log import logger
from app.internal.event import NewFriendRequestEvent
from app.internal.network import respond_new_friend_request_event
from plugins import EventPlugin


class FriendRequestHandler(EventPlugin):
    name = "dicerobot.friend_request"
    description = ""

    events = NewFriendRequestEvent

    def __call__(self) -> None:
        logger.success(f"Friend request from {self.event.from_id} received")

        respond_new_friend_request_event({
            "event_id": self.event.event_id,
            "from_id": self.event.from_id,
            "group_id": self.event.group_id,
            "operate": 0,
            "message": ""
        })

        logger.success(f"Friend request from {self.event.from_id} approved")
