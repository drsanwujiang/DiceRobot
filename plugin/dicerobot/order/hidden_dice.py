from plugin import OrderPlugin
from plugin.dicerobot.order.dice import Dice
from app.exceptions import OrderError
from app.enum import ChatType


class HiddenDice(OrderPlugin):
    name = "dicerobot.hidden_dice"
    display_name = "暗骰"
    description = "掷一个或一堆骰子，并通过私聊发送结果"
    version = "1.0.0"

    default_replies = {
        "reply": "{&发送者}悄悄地进行了掷骰……",
        "reply_with_reason": "由于{&掷骰原因}，{&发送者}悄悄地进行了掷骰",
        "result": "在{&群名}（{&群号}）中骰出了：{&掷骰结果}",
        "result_with_reason": "由于{&掷骰原因}，在{&群名}（{&群号}）中骰出了：{&掷骰结果}",
        "not_in_group": "只能在群聊中使用暗骰哦！"
    }

    orders = [
        r"r\s*h", "暗骰"
    ]
    priority = 10

    def __call__(self) -> None:
        self.check_chat_type()

        dice = Dice(self.message_chain, ".r", self.order_content)
        dice.parse_content()
        dice.calculate_expression()
        dice.generate_results()

        self.update_reply_variables({
            "掷骰原因": dice.reason,
            "掷骰结果": dice.complete_expression
        })
        self.reply_to_sender(self.get_reply(key="reply_with_reason" if dice.reason else "reply"))
        self.send_friend_or_temp_message(
            self.message_chain.sender.id,
            self.message_chain.sender.group.id,
            self.format_reply(self.get_reply(key="result_with_reason" if dice.reason else "result"))
        )

    def check_chat_type(self) -> None:
        if self.chat_type != ChatType.GROUP:
            raise OrderError(self.get_reply(key="not_in_group"))
