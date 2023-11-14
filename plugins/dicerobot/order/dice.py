import re
import random

from plugins import OrderPlugin

from app.config import Config
from app.exceptions import OrderException


class Dice(OrderPlugin):
    name = "dicerobot.dice"
    description = ""
    default_settings = {
        "max_count": 100,
        "max_surface": 1000
    }
    default_replies = {
        "dice_result": "{&发送者}骰出了：{&掷骰结果}",
        "dice_result_with_reason": "由于{&掷骰原因}，{&发送者}骰出了：{&掷骰结果}",
        "max_count_exceeded": "被骰子淹没，不知所措……",
        "max_surface_exceeded": "为什么会有这么多面的骰子啊(　д ) ﾟ ﾟ",
        "expression_invalid": "掷骰表达式不符合规则！",
        "expression_error": "掷骰表达式无法解析！"
    }
    supported_reply_variables = ["掷骰原因", "掷骰结果"]
    default_chat_settings = {
        "default_surface": 100
    }

    orders = [
        "r", "掷骰"
    ]
    default_priority = 1

    _content_pattern = re.compile(r"^([\ddk+\-x*()（）]+)?\s*([\S\s]*)$", re.I)
    _repeated_symbol_pattern = re.compile(r"([dk+\-*])\\1+", re.I)
    _subexpression_split_pattern = re.compile(r"((?:[1-9]\d*)?d(?:[1-9]\d*)?(?:k(?:[1-9]\d*)?)?)", re.I)
    _subexpression_pattern = re.compile(r"^([1-9]\d*)?d([1-9]\d*)?(k([1-9]\d*)?)?$", re.I)
    _math_expression_pattern = re.compile(r"^[\d+\-*()]+$")

    def __init__(self, *args, **kwargs) -> None:
        super().__init__(*args, **kwargs)

        self.expression = "d"
        self.reason = ""
        self.detailed_dice_result = ""
        self.dice_result = ""
        self.final_result = ""

    def __call__(self) -> None:
        self.parse_content()
        self.calculate_expression()

        # Beautify expression and results
        self.expression = self.expression.replace("*", "×")
        self.detailed_dice_result = self.detailed_dice_result.replace("*", "×")
        self.dice_result = self.dice_result.replace("*", "×")

        complete_expression = f"{self.expression}={self.detailed_dice_result}"
        complete_expression += "" if self.detailed_dice_result == self.dice_result else f"={self.dice_result}"
        complete_expression += "" if self.dice_result == self.final_result else f"={self.final_result}"

        self.update_reply_variables({
            "掷骰原因": self.reason,
            "掷骰结果": complete_expression
        })

        self.reply_to_sender(self.replies["dice_result_with_reason" if self.reason else "dice_result"])

    def parse_content(self) -> None:
        match = Dice._content_pattern.fullmatch(self.order_content)
        self.expression = match.group(1) if match.group(1) else self.expression
        self.reason = match.group(2)

    def calculate_expression(self) -> None:
        # Standardize symbols
        self.expression = self.expression.replace("x", "*").replace("X", "*") \
            .replace("（", "(").replace("）", ")")

        # Check continuously repeated symbols like "dd"
        if Dice._repeated_symbol_pattern.fullmatch(self.expression):
            self.expression = "d"
            self.reason = self.order_content
            return self.calculate_expression()

        parts = Dice._subexpression_split_pattern.split(self.expression)
        detailed_parts = parts.copy()
        result_parts = parts.copy()

        for i in range(len(parts)):
            if Dice._subexpression_pattern.fullmatch(parts[i]):
                subexpression = Subexpression(
                    parts[i],
                    self.plugin_settings,
                    self.replies,
                    self.chat_settings
                )
                parts[i] = str(subexpression)
                detailed_parts[i] = subexpression.detailed_dice_result
                result_parts[i] = subexpression.dice_result

        self.expression = "".join(parts)
        self.detailed_dice_result = "".join(detailed_parts)
        self.dice_result = "".join(result_parts)

        if not Dice._math_expression_pattern.fullmatch(self.dice_result):
            raise OrderException(self.replies["expression_invalid"])

        try:
            self.final_result = str(eval(self.dice_result, {}, {}))
        except (ValueError, SyntaxError):
            raise OrderException(self.replies["expression_error"])


class Subexpression:
    subexpression_pattern = re.compile(r"^([1-9]\d*)?d([1-9]\d*)?(?:k([1-9]\d*)?)?$", re.I)

    def __init__(
        self,
        subexpression: str,
        plugin_settings: Config[str, str],
        replies: Config[str, str],
        chat_settings: Config[str, str]
    ) -> None:
        self.plugin_settings = plugin_settings
        self.replies = replies
        self.chat_settings = chat_settings

        self.subexpression = subexpression
        self.count = 0
        self.surface = 0
        self.max_result_count = 0
        self.detailed_dice_result = ""
        self.dice_result = ""

        self.parse()
        self.check_range()
        self.calculate()

    def parse(self) -> None:
        match = Subexpression.subexpression_pattern.fullmatch(self.subexpression)
        self.count = int(match.group(1)) if match.group(1) else 1
        self.surface = int(match.group(2)) if match.group(2) else self.chat_settings["default_surface"]
        self.max_result_count = int(match.group(3)) if match.group(3) else 0

    def check_range(self) -> None:
        if self.count > self.plugin_settings["max_count"]:
            raise OrderException(self.replies["max_count_exceeded"])
        elif self.surface > self.plugin_settings["max_surface"]:
            raise OrderException(self.replies["max_surface_exceeded"])
        elif self.max_result_count > self.count:
            raise OrderException(self.replies["expression_invalid"])

    def calculate(self) -> None:
        results = [random.randint(1, self.surface) for _ in range(self.count)]

        if self.max_result_count > 0:
            results.sort(reverse=True)
            results = results[:self.max_result_count]

        self.detailed_dice_result = "(" + "+".join(map(str, results)) + ")" if self.count > 1 else str(results[0])
        self.dice_result = str(sum(results))

    def __str__(self) -> str:
        subexpression = str(self.count) if self.count > 1 else ""
        subexpression += "D" + str(self.surface)
        subexpression += "K" + str(self.max_result_count) if self.max_result_count > 0 else ""
        return subexpression
