from loguru import logger

from app.models.report.request import GroupRequest
from plugin import EventPlugin


class GroupInvitationHandler(EventPlugin):
    name = "dicerobot.group_invite"
    display_name = "群聊邀请"
    description = "处理群聊邀请"
    version = "1.2.0"
    default_plugin_settings = {
        "auto_accept": True
    }
    events = [
        GroupRequest
    ]

    async def __call__(self) -> None:
        logger.success(f"Group invitation from {self.event.group_id} received")

        if self.plugin_settings["auto_accept"]:
            await self.context.network_manager.napcat.set_group_add_request(self.event.flag, self.event.sub_type, True)
            logger.success(f"Group invitation from {self.event.group_id} automatically accepted")
