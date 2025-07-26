import re
import random

from plugin import OrderPlugin
from app.exceptions import OrderSuspiciousError, OrderError


class BPDice(OrderPlugin):
    name = "dicerobot.bp_dice"
    display_name = "奖励骰/惩罚骰"
    description = "掷一个骰子，以及一个或多个奖励骰/惩罚骰"
    version = "1.2.0"
    priority = 10
    max_repetition = 30
    orders = [
        r"r\s*b", "奖励骰",
        r"r\s*p", "惩罚骰"
    ]
    default_plugin_settings = {
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
        self.full_result = ""

    async def __call__(self) -> None:
        self.check_repetition()
        self.parse_content()
        self.bonus_or_penalty()
        result = self.full_result

        if self.repetition > 1:
            result = f"\n{result}"

            for _ in range(self.repetition - 1):
                self.bonus_or_penalty()
                result += f"\n{self.full_result}"

        self.update_reply_variables({
            "掷骰原因": self.reason,
            "掷骰结果": result
        })
        await self.reply_to_sender(self.replies["result_with_reason" if self.reason else "result"])

    def parse_content(self) -> None:
        if self.order in self._bp_types["bonus"]:
            self.bp_type = "bonus"
        elif self.order in self._bp_types["penalty"]:
            self.bp_type = "penalty"
        else:
            raise OrderError("Invalid order")

        # Parse order content into possible count and reason
        match = self._content_pattern.fullmatch(self.order_content)

        # Check count length
        if len(match.group(1) or "") > 3:
            raise OrderSuspiciousError

        self.count = int(match.group(1)) if match.group(1) else self.count
        self.reason = match.group(2)

        # Check count
        if self.count > self.plugin_settings["max_count"]:
            raise OrderError(self.replies["max_count_exceeded"])

    def bonus_or_penalty(self) -> None:
        bp_type_name = None

        # Calculate result
        self.dice_result = random.randint(1, 100)
        self.bp_results = [random.randint(1, 10) for _ in range(self.count)]

        # Calculate final result
        ones = self.dice_result % 10
        tens = self.dice_result // 10

        if self.bp_type == "bonus":
            bp_type_name = "奖励骰"
            min_result = min(self.bp_results)
            tens = min_result if tens > min_result else tens
        elif self.bp_type == "penalty":
            bp_type_name = "惩罚骰"
            max_result = max(self.bp_results)
            tens = max_result if tens < max_result else tens

        self.final_result = tens * 10 + ones
        self.final_result = 100 if self.final_result > 100 else self.final_result

        detailed_bp_result = " ".join(map(str, self.bp_results))
        self.full_result = f"B{self.count}={self.dice_result}[{bp_type_name}:{detailed_bp_result}]={self.final_result}"
