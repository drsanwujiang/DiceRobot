from app.log import logger
from app.internal.event import BotInvitedJoinGroupRequestEvent
from app.internal.network import respond_bot_invited_join_group_request_event
from plugins import EventPlugin


class GroupInvitationHandler(EventPlugin):
    name = "dicerobot.group_invite"
    display_name = "群聊邀请"
    description = "处理群聊邀请"
    version = "1.0.0"

    default_settings = {
        "auto_accept": True
    }

    events = BotInvitedJoinGroupRequestEvent

    def __call__(self) -> None:
        logger.success(f"Group invitation from {self.event.group_id} received")

        if self.settings["auto_accept"]:
            respond_bot_invited_join_group_request_event({
                "event_id": self.event.event_id,
                "from_id": self.event.from_id,
                "group_id": self.event.group_id,
                "operate": 0,
                "message": ""
            })

            logger.success(f"Group invitation from {self.event.group_id} automatically accepted")
