from plugin import OrderPlugin
from plugin.dicerobot.order.dice import Dice
from app.config import status
from app.exceptions import OrderError
from app.models.report.message import GroupMessage
from app.network.napcat import get_group_info


class HiddenDice(OrderPlugin):
    name = "dicerobot.hidden_dice"
    display_name = "暗骰"
    description = "掷一个或一堆骰子，并通过私聊发送结果"
    version = "1.3.0"

    default_replies = {
        "reply": "{&发送者}悄悄地进行了掷骰……",
        "reply_with_reason": "由于{&掷骰原因}，{&发送者}悄悄地进行了掷骰",
        "result": "在{&群名}（{&群号}）中骰出了：{&掷骰结果}",
        "result_with_reason": "由于{&掷骰原因}，在{&群名}（{&群号}）中骰出了：{&掷骰结果}",
        "not_in_group": "只能在群聊中使用暗骰哦！",
        "not_friend": "必须先添加好友才能使用暗骰哦！"
    }

    orders = [
        r"r\s*h", "暗骰"
    ]
    priority = 10
    max_repetition = 30

    async def __call__(self) -> None:
        self.check_chat_type()
        self.check_friend()
        self.check_repetition()

        dice = Dice(self.message, ".r", self.order_content)
        dice.roll()
        result = dice.full_result

        if self.repetition > 1:
            result = f"\n{result}"

            for _ in range(self.repetition - 1):
                dice.roll()
                result += f"\n{dice.full_result}"

        assert isinstance(self.message, GroupMessage)
        self.update_reply_variables({
            "掷骰原因": dice.reason,
            "掷骰结果": result,
            "群号": self.message.group_id,
            "群名": (await get_group_info(self.message.group_id)).data.group_name
        })
        await self.reply_to_sender(self.replies["reply_with_reason" if dice.reason else "reply"])
        await self.send_friend_message(
            self.message.user_id,
            self.format_reply(self.replies["result_with_reason" if dice.reason else "result"])
        )

    def check_chat_type(self) -> None:
        if not isinstance(self.message, GroupMessage):
            raise OrderError(self.replies["not_in_group"])

    def check_friend(self) -> None:
        if self.message.user_id not in status.bot.friends:
            raise OrderError(self.replies["not_friend"])
