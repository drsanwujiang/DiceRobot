"""掷骰表达式的词法与语法分析。

采用递归下降而非正则切分：正则无法处理 ``(2d6+3)*2`` 这类嵌套，也无法在出错时给出
位置。

文法::

    expression := term (("+" | "-") term)*
    term       := factor (("*" | "/") factor)*
    factor     := ("+" | "-") factor | primary
    primary    := dice | NUMBER | "(" expression ")"
    dice       := [NUMBER] "d" [NUMBER] [("k" | "kl") [NUMBER]]
"""

from __future__ import annotations

from dataclasses import dataclass
from enum import StrEnum, auto

from dicerobot.trpg.dice.ast import Binary, BinaryOperator, Dice, Node, Number, Unary, UnaryOperator
from dicerobot.trpg.dice.errors import DiceSyntaxError

__all__ = ["MAX_EXPRESSION_LENGTH", "parse"]

MAX_EXPRESSION_LENGTH = 200
"""表达式长度上限，超长输入在解析前即被拒绝。"""

# 输入端的等价写法统一折算为标准形式：全角字符来自中文输入法，x 作乘号是常见写法。
# 文法中除 d、k、l 外不使用字母，故 x 不产生歧义。
_NORMALIZE = str.maketrans(
    {
        "（": "(",
        "）": ")",
        "×": "*",
        "✕": "*",
        "x": "*",
        "X": "*",
        "÷": "/",
        "－": "-",
        "＋": "+",
    }
)


class TokenKind(StrEnum):
    NUMBER = auto()
    DICE = auto()
    KEEP = auto()
    KEEP_LOWEST = auto()
    PLUS = auto()
    MINUS = auto()
    STAR = auto()
    SLASH = auto()
    LPAREN = auto()
    RPAREN = auto()
    END = auto()


@dataclass(frozen=True, slots=True)
class Token:
    kind: TokenKind
    position: int
    value: int = 0


# 词法记号到语法树运算符的映射。以表代替三元表达式，使类型检查器可确认取值范围。
_BINARY_OPERATORS: dict[TokenKind, BinaryOperator] = {
    TokenKind.PLUS: "+",
    TokenKind.MINUS: "-",
    TokenKind.STAR: "*",
    TokenKind.SLASH: "/",
}
_UNARY_OPERATORS: dict[TokenKind, UnaryOperator] = {
    TokenKind.PLUS: "+",
    TokenKind.MINUS: "-",
}

_OPERATORS = {
    "+": TokenKind.PLUS,
    "-": TokenKind.MINUS,
    "*": TokenKind.STAR,
    "/": TokenKind.SLASH,
    "(": TokenKind.LPAREN,
    ")": TokenKind.RPAREN,
}


def parse(expression: str) -> Node:
    """把表达式解析为语法树。

    Args:
        expression: 掷骰表达式，如 ``3d6+2``、``4d6k3``、``(2d6+3)*2``。

    Raises:
        DiceSyntaxError: 表达式为空、过长或不符合文法。
    """

    if len(expression) > MAX_EXPRESSION_LENGTH:
        raise DiceSyntaxError(f"表达式过长（上限 {MAX_EXPRESSION_LENGTH} 个字符）", MAX_EXPRESSION_LENGTH)

    tokens = _tokenize(expression.translate(_NORMALIZE))

    if tokens[0].kind is TokenKind.END:
        raise DiceSyntaxError("表达式为空", 0)

    return _Parser(tokens).parse()


def _tokenize(expression: str) -> list[Token]:
    tokens: list[Token] = []
    index = 0

    while index < len(expression):
        char = expression[index]

        if char.isspace():
            index += 1
            continue

        # 须用 isdecimal 而非 isdigit：上标等字符 isdigit 为真但 int() 无法转换。
        # isdecimal 与 int() 接受的范围一致，并可识别全角数字。
        if char.isdecimal():
            start = index

            while index < len(expression) and expression[index].isdecimal():
                index += 1

            tokens.append(Token(TokenKind.NUMBER, start, int(expression[start:index])))
            continue

        lowered = char.lower()

        if lowered == "d":
            tokens.append(Token(TokenKind.DICE, index))
            index += 1
            continue

        if lowered == "k":
            # kl 表示保留最低的若干个，需多看一个字符加以区分。
            if index + 1 < len(expression) and expression[index + 1].lower() == "l":
                tokens.append(Token(TokenKind.KEEP_LOWEST, index))
                index += 2
            else:
                tokens.append(Token(TokenKind.KEEP, index))
                index += 1

            continue

        if (kind := _OPERATORS.get(char)) is not None:
            tokens.append(Token(kind, index))
            index += 1
            continue

        raise DiceSyntaxError(f"无法识别的字符 {char!r}", index)

    tokens.append(Token(TokenKind.END, len(expression)))

    return tokens


class _Parser:
    """递归下降解析器。"""

    def __init__(self, tokens: list[Token]) -> None:
        self._tokens = tokens
        self._index = 0

    def parse(self) -> Node:
        node = self._expression()

        if self._peek().kind is not TokenKind.END:
            raise DiceSyntaxError("表达式末尾有多余的内容", self._peek().position)

        return node

    def _peek(self) -> Token:
        """查看当前记号但不消耗。

        写为方法而非属性：返回值随解析推进而变化，属性形式会使类型检查器跨语句收窄
        类型，进而把后续分支判为不可达。
        """

        return self._tokens[self._index]

    def _advance(self) -> Token:
        token = self._tokens[self._index]
        self._index += 1

        return token

    def _accept(self, *kinds: TokenKind) -> Token | None:
        if self._peek().kind in kinds:
            return self._advance()

        return None

    def _expression(self) -> Node:
        node = self._term()

        while token := self._accept(TokenKind.PLUS, TokenKind.MINUS):
            node = Binary(operator=_BINARY_OPERATORS[token.kind], left=node, right=self._term())

        return node

    def _term(self) -> Node:
        node = self._factor()

        while token := self._accept(TokenKind.STAR, TokenKind.SLASH):
            node = Binary(operator=_BINARY_OPERATORS[token.kind], left=node, right=self._factor())

        return node

    def _factor(self) -> Node:
        if token := self._accept(TokenKind.PLUS, TokenKind.MINUS):
            return Unary(operator=_UNARY_OPERATORS[token.kind], operand=self._factor())

        return self._primary()

    def _primary(self) -> Node:
        if self._accept(TokenKind.LPAREN):
            node = self._expression()

            if not self._accept(TokenKind.RPAREN):
                raise DiceSyntaxError("括号没有闭合", self._peek().position)

            return node

        if self._peek().kind is TokenKind.DICE:
            return self._dice(count=None)

        if token := self._accept(TokenKind.NUMBER):
            # 数字后紧跟 d 才构成骰子，否则为普通字面量。
            if self._peek().kind is TokenKind.DICE:
                return self._dice(count=token.value)

            return Number(value=token.value)

        raise DiceSyntaxError("这里需要一个数字、骰子或括号", self._peek().position)

    def _dice(self, *, count: int | None) -> Dice:
        self._advance()  # 消耗 d

        surface = token.value if (token := self._accept(TokenKind.NUMBER)) else None
        keep: int | None = None
        keep_lowest = False

        if token := self._accept(TokenKind.KEEP, TokenKind.KEEP_LOWEST):
            keep_lowest = token.kind is TokenKind.KEEP_LOWEST
            # 只写 k 不写数量时保留一颗，与常见掷骰记法一致。
            keep = number.value if (number := self._accept(TokenKind.NUMBER)) else 1

        return Dice(count=count, surface=surface, keep=keep, keep_lowest=keep_lowest)
