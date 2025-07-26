import re
import random

from plugin import OrderPlugin
from app.exceptions import OrderSuspiciousError, OrderError


class Dice(OrderPlugin):
    name = "dicerobot.dice"
    display_name = "掷骰"
    description = "掷一个或一堆骰子"
    version = "1.2.0"
    priority = 1
    max_repetition = 30
    orders = [
        "r", "掷骰"
    ]
    default_plugin_settings = {
        "max_count": 100,
        "max_surface": 1000
    }
    default_chat_settings = {
        "default_surface": 100
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
    _content_pattern = re.compile(r"^([\ddk+\-*x×()（）]+)?\s*([\S\s]*)$", re.I)
    _repeated_symbol_pattern = re.compile(r"([dk+\-*])\1+", re.I)
    _dice_expression_split_pattern = re.compile(r"((?:[1-9]\d*)?d(?:[1-9]\d*)?(?:k(?:[1-9]\d*)?)?)", re.I)
    _dice_expression_pattern = re.compile(r"^([1-9]\d*)?d([1-9]\d*)?(k([1-9]\d*)?)?$", re.I)
    _math_expression_pattern = re.compile(r"^[\d+\-*()]+$")

    def __init__(self, *args, **kwargs) -> None:
        super().__init__(*args, **kwargs)

        self.expression = "d"
        self.reason = ""

        self.detailed_result = ""
        self.brief_result = ""
        self.final_result = ""
        self.full_result = ""

    async def __call__(self) -> None:
        self.check_repetition()
        self.roll()
        result = self.full_result

        if self.repetition > 1:
            result = f"\n{result}"

            for _ in range(self.repetition - 1):
                self.roll()
                result += f"\n{self.full_result}"

        self.update_reply_variables({
            "掷骰原因": self.reason,
            "掷骰结果": result
        })
        await self.reply_to_sender(self.replies["result_with_reason" if self.reason else "result"])

    def roll(self) -> None:
        self.parse_content()
        self.calculate_expression()
        self.generate_results()

    def parse_content(self) -> None:
        # Parse order content into possible expression and reason
        match = self._content_pattern.fullmatch(self.order_content)

        # Check expression length
        if len(match.group(1) or "") > 100:
            raise OrderSuspiciousError

        self.expression = match.group(1) if match.group(1) else self.expression
        self.reason = match.group(2)

    def calculate_expression(self) -> None:
        # Standardize symbols
        self.expression = self.expression \
            .replace("x", "*").replace("X", "*").replace("×", "*") \
            .replace("（", "(").replace("）", ")")

        # Check continuously repeated symbols like "dd"
        if self._repeated_symbol_pattern.fullmatch(self.expression):
            self.expression = "d"
            self.reason = self.order_content
            return self.calculate_expression()

        # Search possible dice expressions
        parts = self._dice_expression_split_pattern.split(self.expression)
        detailed_parts = parts.copy()
        result_parts = parts.copy()

        for i in range(len(parts)):
            # Check if the part is dice expression like "D100", "2D50" or "5D10K2"
            if self._dice_expression_pattern.fullmatch(parts[i]):
                dice_expression = DiceExpression(
                    parts[i],
                    self.chat_settings["default_surface"],
                    self.plugin_settings["max_count"],
                    self.plugin_settings["max_surface"]
                )

                # Check count, surface and max result count (K number)
                if dice_expression.count > dice_expression.max_count:
                    raise OrderError(self.replies["max_count_exceeded"])
                elif dice_expression.surface > dice_expression.max_surface:
                    raise OrderError(self.replies["max_surface_exceeded"])
                elif dice_expression.max_result_count > dice_expression.count:
                    raise OrderError(self.replies["expression_invalid"])

                # Calculate results
                dice_expression.calculate()

                parts[i] = str(dice_expression)
                detailed_parts[i] = dice_expression.detailed_dice_result
                result_parts[i] = dice_expression.dice_result

        # Reassemble expression and results
        self.expression = "".join(parts)
        self.detailed_result = "".join(detailed_parts)
        self.brief_result = "".join(result_parts)

        if not self._math_expression_pattern.fullmatch(self.brief_result):
            raise OrderError(self.replies["expression_invalid"])

        try:
            self.final_result = str(eval(self.brief_result, {}, {}))
        except (ValueError, SyntaxError):
            raise OrderError(self.replies["expression_error"])

    def generate_results(self) -> None:
        # Beautify expression and results
        self.expression = self.expression.replace("*", "×")
        self.detailed_result = self.detailed_result.replace("*", "×")
        self.brief_result = self.brief_result.replace("*", "×")

        # Omit duplicate results
        result = "=".join(list(dict.fromkeys([self.detailed_result, self.brief_result, self.final_result])))
        self.full_result = f"{self.expression}={result}"


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
        match = self.expression_pattern.fullmatch(self.expression)
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
