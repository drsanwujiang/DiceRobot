from app.log import logger
from app.internal.event import BotInvitedJoinGroupRequestEvent
from app.internal.network import respond_bot_invited_join_group_request_event
from plugins import EventPlugin


class GroupInvitationHandler(EventPlugin):
    name = "dicerobot.group_invite"
    description = ""

    events = BotInvitedJoinGroupRequestEvent

    def __call__(self) -> None:
        logger.success(f"Group invitation from {self.event.group_id} received")

        respond_bot_invited_join_group_request_event({
            "event_id": self.event.event_id,
            "from_id": self.event.from_id,
            "group_id": self.event.group_id,
            "operate": 0,
            "message": ""
        })

        logger.success(f"Group invitation from {self.event.group_id} approved")
