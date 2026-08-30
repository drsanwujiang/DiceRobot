"""掷骰相关的异常。

领域层不感知消息的去向，仅描述错误性质，面向玩家的措辞由指令层转换。位置信息一路
上传，以便在报错时指出出错的字符。
"""

from __future__ import annotations

from dicerobot.errors import DiceRobotError

__all__ = ["DiceError", "DiceEvaluationError", "DiceLimitError", "DiceSyntaxError"]


class DiceError(DiceRobotError):
    """掷骰表达式无法处理。"""


class DiceSyntaxError(DiceError):
    """表达式不符合文法。

    Attributes:
        position: 出错字符在表达式中的下标。
    """

    def __init__(self, message: str, position: int) -> None:
        super().__init__(message)

        self.message = message
        self.position = position


class DiceLimitError(DiceError):
    """骰子个数、面数或保留数量超出允许范围。"""


class DiceEvaluationError(DiceError):
    """表达式合法但无法求值，如除以零。"""
