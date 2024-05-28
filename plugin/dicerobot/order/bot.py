from datetime import date

from plugin import OrderPlugin
from app.config import status
from app.exceptions import OrderInvalidError, OrderError
from app.enum import ChatType
from app.models.network.mirai import SetGroupMemberInfoRequest
from app.network.mirai import set_group_member_info


class Bot(OrderPlugin):
    name = "dicerobot.bot"
    display_name = "Bot 控制"
    description = "与 Bot 有关的各种指令"
    version = "1.0.0"

    default_replies = {
        "about": "DiceRobot {&版本}\nMIT License\n© 2019-{&当前年份} Drsanwujiang",
        "enable": "呐呐~{&机器人昵称}为你服务~☆♪",
        "enable_denied": "只有群主/管理员才可以叫醒人家哦~",
        "disable": "(´〜｀*) zzzZZZ",
        "disable_denied": "但是群主/管理员还没有让人家休息呀……",
        "nickname_set": "之后就请称呼我为「{&机器人昵称}」吧~",
        "nickname_unset": "真·名·解·放",
        "nickname_denied": "只有群主/管理员才可以修改人家的名字哦~"
    }
    supported_reply_variables = [
        "版本",
        "版权信息"
    ]

    orders = [
        "bot", "robot"
    ]
    priority = 10

    _suborders = {
        "about": ["about", "info", "关于", "信息"],
        "enable": ["on", "start", "enable", "开启", "启动"],
        "disable": ["off", "stop", "disable", "关闭", "停止"],
        "nickname": ["nickname", "name", "nn", "昵称"]
    }

    def __init__(self, *args, **kwargs) -> None:
        super().__init__(*args, **kwargs)

        self.suborder = ""
        self.suborder_content = ""

        for suborder, suborders in Bot._suborders.items():
            for _suborder in suborders:
                if self.order_content.startswith(_suborder):
                    self.suborder = suborder
                    self.suborder_content = self.order_content[len(_suborder):].strip()

    def check_enabled(self) -> bool:
        if self.suborder == "enable":
            return True

        return super().check_enabled()

    def __call__(self) -> None:
        if self.suborder == "" or self.suborder == "about":
            self.about()
        elif self.suborder == "enable":
            self.enable()
        elif self.suborder == "disable":
            self.disable()
        elif self.suborder == "nickname":
            self.nickname()
        else:
            raise OrderInvalidError()

    def about(self) -> None:
        if self.suborder_content:
            raise OrderInvalidError()

        self.update_reply_variables({
            "版本": status["version"],
            "当前年份": date.today().year
        })
        self.reply_to_sender(self.get_reply(key="about"))

    def enable(self) -> None:
        # Ignore if not in group chat
        if self.chat_type != ChatType.GROUP:
            return

        if self.suborder_content:
            raise OrderInvalidError()

        if self.message_chain.sender.permission not in ["OWNER", "ADMINISTRATOR"]:
            raise OrderError(self.get_reply(key="enable_denied"))

        self.set_chat_setting(group="dicerobot", key="enabled", value=True)
        self.reply_to_sender(self.get_reply(key="enable"))

    def disable(self) -> None:
        # Ignore if not in group chat
        if self.chat_type != ChatType.GROUP:
            return

        if self.suborder_content:
            raise OrderInvalidError()

        if self.message_chain.sender.permission not in ["OWNER", "ADMINISTRATOR"]:
            raise OrderError(self.get_reply(key="disable_denied"))

        self.set_chat_setting(group="dicerobot", key="enabled", value=False)
        self.reply_to_sender(self.get_reply(key="disable"))

    def nickname(self) -> None:
        # Ignore if not in group chat
        if self.chat_type != ChatType.GROUP:
            return

        if self.message_chain.sender.permission not in ["OWNER", "ADMINISTRATOR"]:
            raise OrderError(self.get_reply(key="nickname_denied"))

        if self.suborder_content:
            # Set nickname
            self.set_chat_setting(group="dicerobot", key="nickname", value=self.suborder_content)
            set_group_member_info(SetGroupMemberInfoRequest(
                target=self.chat_id,
                member_id=status["bot"]["id"],
                info=SetGroupMemberInfoRequest.Info(
                    name=self.suborder_content
                )
            ))
            self.update_reply_variables({
                "机器人": self.suborder_content,
                "机器人昵称": self.suborder_content
            })
            self.reply_to_sender(self.get_reply(key="nickname_set"))
        else:
            # Unset nickname
            self.set_chat_setting(group="dicerobot", key="nickname", value="")
            set_group_member_info(SetGroupMemberInfoRequest(
                target=self.chat_id,
                member_id=status["bot"]["id"],
                info=SetGroupMemberInfoRequest.Info(
                    name=""
                )
            ))
            self.update_reply_variables({
                "机器人": status["bot"]["nickname"],
                "机器人昵称": status["bot"]["nickname"]
            })
            self.reply_to_sender(self.get_reply(key="nickname_unset"))
