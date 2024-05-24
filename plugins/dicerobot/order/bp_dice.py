import re
import random

from plugins import OrderPlugin
from app.exceptions import OrderException


class BPDice(OrderPlugin):
    name = "dicerobot.bp_dice"
    display_name = "奖励骰/惩罚骰"
    description = "掷一个骰子，以及一个或多个奖励骰/惩罚骰"
    version = "1.0.0"

    default_settings = {
        "max_count": 100
    }
    default_replies = {
        "result": "{&发送者}骰出了：{&掷骰结果}",
        "result_with_reason": "由于{&掷骰原因}，{&发送者}骰出了：{&掷骰结果}",
        "max_count_exceeded": "被骰子淹没，不知所措……"
    }
    supported_reply_variables = [
        "掷骰原因",
        "掷骰结果"
    ]

    orders = [
        r"r\s*b", "奖励骰",
        r"r\s*p", "惩罚骰"
    ]
    priority = 10

    _content_pattern = re.compile(r"^([1-9]\d*)?\s*([\S\s]*)$", re.I)
    _bp_types = {
        "bonus": ["rb", "奖励骰"],
        "penalty": ["rp", "惩罚骰"]
    }

    def __init__(self, *args, **kwargs) -> None:
        super().__init__(*args, **kwargs)

        self.order = re.sub(r"\s", "", self.order)  # Remove whitespace characters
        self.bp_type = None

        self.count = 1
        self.reason = ""

        self.dice_result = -1
        self.bp_results: list[int] = []
        self.final_result = -1

    def __call__(self) -> None:
        self.parse_content()
        self.calculate()
        self.bonus_or_penalty()

        bp_type = "奖励骰" if self.bp_type == "bonus" else "惩罚骰" if self.bp_type == "penalty" else ""
        result = f"B{self.count}={self.dice_result}[{bp_type}:{' '.join(map(str, self.bp_results))}]={self.final_result}"

        self.update_reply_variables({
            "掷骰原因": self.reason,
            "掷骰结果": result
        })
        self.reply_to_sender(self.get_reply("result_with_reason" if self.reason else "result"))

    def parse_content(self) -> None:
        if self.order in BPDice._bp_types["bonus"]:
            self.bp_type = "bonus"
        elif self.order in BPDice._bp_types["penalty"]:
            self.bp_type = "penalty"
        else:
            raise OrderException("Invalid order")

        # Parse order content into possible count and reason
        match = BPDice._content_pattern.fullmatch(self.order_content)
        self.count = int(match.group(1)) if match.group(1) else self.count
        self.reason = match.group(2)

    def calculate(self) -> None:
        # Check count
        if self.count > self.get_setting("max_count"):
            raise OrderException(self.get_reply("max_count_exceeded"))

        # Calculate result
        self.dice_result = random.randint(1, 100)
        self.bp_results = [random.randint(1, 10) for _ in range(self.count)]

    def bonus_or_penalty(self) -> None:
        ones = self.dice_result % 10
        tens = self.dice_result // 10

        if self.bp_type == "bonus":
            min_result = min(self.bp_results)
            tens = min_result if tens > min_result else tens
        elif self.bp_type == "penalty":
            max_result = max(self.bp_results)
            tens = max_result if tens < max_result else tens

        self.final_result = tens * 10 + ones
        self.final_result = 100 if self.final_result > 100 else self.final_result
