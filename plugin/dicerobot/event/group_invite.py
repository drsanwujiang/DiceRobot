from plugin import EventPlugin
from app.log import logger
from app.models.report.request import GroupAddRequest
from app.network.napcat import set_group_add_request


class GroupInvitationHandler(EventPlugin):
    name = "dicerobot.group_invite"
    display_name = "群聊邀请"
    description = "处理群聊邀请"
    version = "1.1.0"

    default_plugin_settings = {
        "auto_accept": True
    }

    events = [
        GroupAddRequest
    ]

    def __call__(self) -> None:
        logger.success(f"Group invitation from {self.event.group_id} received")

        if self.plugin_settings["auto_accept"]:
            set_group_add_request(self.event.flag, self.event.sub_type, True)

            logger.success(f"Group invitation from {self.event.group_id} automatically accepted")
