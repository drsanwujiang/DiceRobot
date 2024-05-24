import re
import random

from app.exceptions import OrderException
from plugins import OrderPlugin


class Dice(OrderPlugin):
    name = "dicerobot.dice"
    display_name = "掷骰"
    description = "掷一个或一堆骰子"
    version = "1.0.0"

    default_settings = {
        "max_count": 100,
        "max_surface": 1000
    }
    default_replies = {
        "result": "{&发送者}骰出了：{&掷骰结果}",
        "result_with_reason": "由于{&掷骰原因}，{&发送者}骰出了：{&掷骰结果}",
        "max_count_exceeded": "被骰子淹没，不知所措……",
        "max_surface_exceeded": "为什么会有这么多面的骰子啊(　д ) ﾟ ﾟ",
        "expression_invalid": "掷骰表达式不符合规则……",
        "expression_error": "掷骰表达式无法解析……"
    }
    supported_reply_variables = [
        "掷骰原因",
        "掷骰结果"
    ]
    default_chat_settings = {
        "default_surface": 100
    }

    orders = [
        "r", "掷骰"
    ]
    priority = 1

    _content_pattern = re.compile(r"^([\ddk+\-x*()（）]+)?\s*([\S\s]*)$", re.I)
    _repeated_symbol_pattern = re.compile(r"([dk+\-*])\1+", re.I)
    _dice_expression_split_pattern = re.compile(r"((?:[1-9]\d*)?d(?:[1-9]\d*)?(?:k(?:[1-9]\d*)?)?)", re.I)
    _dice_expression_pattern = re.compile(r"^([1-9]\d*)?d([1-9]\d*)?(k([1-9]\d*)?)?$", re.I)
    _math_expression_pattern = re.compile(r"^[\d+\-*()]+$")

    def __init__(self, *args, **kwargs) -> None:
        super().__init__(*args, **kwargs)

        self.expression = "d"
        self.reason = ""

        self.detailed_dice_result = ""
        self.dice_result = ""
        self.final_result = ""
        self.complete_expression = ""

    def __call__(self) -> None:
        self.parse_content()
        self.calculate_expression()
        self.generate_results()

        self.update_reply_variables({
            "掷骰原因": self.reason,
            "掷骰结果": self.complete_expression
        })
        self.reply_to_sender(self.get_reply("result_with_reason" if self.reason else "result"))

    def parse_content(self) -> None:
        # Parse order content into possible expression and reason
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

        # Search possible dice expressions
        parts = Dice._dice_expression_split_pattern.split(self.expression)
        detailed_parts = parts.copy()
        result_parts = parts.copy()

        for i in range(len(parts)):
            # Check if the part is dice expression like "D100", "2D50" or "5D10K2"
            if Dice._dice_expression_pattern.fullmatch(parts[i]):
                dice_expression = DiceExpression(
                    parts[i],
                    self.get_chat_setting("default_surface"),
                    self.get_setting("max_count"),
                    self.get_setting("max_surface")
                )

                # Check count, surface and max result count (K number)
                if dice_expression.count > dice_expression.max_count:
                    raise OrderException(self.get_reply("max_count_exceeded"))
                elif dice_expression.surface > dice_expression.max_surface:
                    raise OrderException(self.get_reply("max_surface_exceeded"))
                elif dice_expression.max_result_count > dice_expression.count:
                    raise OrderException(self.get_reply("expression_invalid"))

                # Calculate results
                dice_expression.calculate()

                parts[i] = str(dice_expression)
                detailed_parts[i] = dice_expression.detailed_dice_result
                result_parts[i] = dice_expression.dice_result

        # Reassemble expression and results
        self.expression = "".join(parts)
        self.detailed_dice_result = "".join(detailed_parts)
        self.dice_result = "".join(result_parts)

        if not Dice._math_expression_pattern.fullmatch(self.dice_result):
            raise OrderException(self.get_reply("expression_invalid"))

        try:
            self.final_result = str(eval(self.dice_result, {}, {}))
        except (ValueError, SyntaxError):
            raise OrderException(self.get_reply("expression_error"))

    def generate_results(self) -> None:
        # Beautify expression and results
        self.expression = self.expression.replace("*", "×")
        self.detailed_dice_result = self.detailed_dice_result.replace("*", "×")
        self.dice_result = self.dice_result.replace("*", "×")

        # Omit duplicate results
        self.complete_expression = f"{self.expression}={self.detailed_dice_result}"
        self.complete_expression += "" if self.detailed_dice_result == self.dice_result else f"={self.dice_result}"
        self.complete_expression += "" if self.dice_result == self.final_result else f"={self.final_result}"


class DiceExpression:
    expression_pattern = re.compile(r"^([1-9]\d*)?d([1-9]\d*)?(?:k([1-9]\d*)?)?$", re.I)

    def __init__(
        self,
        expression: str,
        default_surface: int,
        max_count: int,
        max_surface: int
    ) -> None:
        self.default_surface = default_surface
        self.max_count = max_count
        self.max_surface = max_surface

        self.expression = expression
        self.count = 0
        self.surface = 0
        self.max_result_count = 0
        self.detailed_dice_result = ""
        self.dice_result = ""

        self.parse()

    def parse(self) -> None:
        match = DiceExpression.expression_pattern.fullmatch(self.expression)
        self.count = int(match.group(1)) if match.group(1) else 1
        self.surface = int(match.group(2)) if match.group(2) else self.default_surface
        self.max_result_count = int(match.group(3)) if match.group(3) else 0

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
