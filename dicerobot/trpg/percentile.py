"""d100 的十位骰与个位骰。

奖励骰与惩罚骰来自 COC：d100 实际由一个十位骰与一个个位骰掷出，追加若干个十位骰后，
奖励骰取结果最小的一个，惩罚骰取最大的一个。个位骰始终只掷一次，与每个十位骰分别组合。

十位与个位都是 0 时结果为 100，因此**必须先组合成结果再比较**：个位为 0、十位掷出 0 与 9
时候选是 100 与 90，奖励骰取 90；若改比十位数字则会取到 0，反而得出 100。

本模块不做任何 IO，随机源由调用方注入，掷骰引擎与检定共用同一套取舍逻辑。
"""

from __future__ import annotations

import random
from dataclasses import dataclass

__all__ = ["BONUS_LABEL", "PENALTY_LABEL", "PercentileRoll", "combine", "roll_percentile"]

BONUS_LABEL = "奖励骰"
PENALTY_LABEL = "惩罚骰"


def combine(tens: int, units: int) -> int:
    """把十位骰与个位骰组合为 1 到 100 的结果。"""

    return 100 if tens == 0 and units == 0 else tens * 10 + units


@dataclass(frozen=True, slots=True)
class PercentileRoll:
    """一次 d100 的结果。

    Attributes:
        value: 最终采用的结果。
        units: 个位骰。
        tens: 全部十位骰，首个为基础骰，其余为追加的奖惩骰，按掷出顺序排列。
        penalty: 追加的是惩罚骰。没有追加骰时该字段无意义。
    """

    value: int
    units: int
    tens: tuple[int, ...]
    penalty: bool

    @property
    def base(self) -> int:
        """基础 d100，即首个十位骰与个位骰的组合。"""

        return combine(self.tens[0], self.units)

    def __str__(self) -> str:
        """展开为 ``52[奖励骰:3 6 9]``；没有追加骰时即结果本身。

        方括号内是追加的十位骰原值。不写成 10：那会让人以为该档结果是 100，而十位 0
        只在个位也是 0 时才记 100，否则是 01 到 09。
        """

        if len(self.tens) == 1:
            return str(self.value)

        label = PENALTY_LABEL if self.penalty else BONUS_LABEL
        extra = " ".join(str(tens) for tens in self.tens[1:])

        return f"{self.base}[{label}:{extra}]"


def roll_percentile(*, rng: random.Random, extra: int = 0, penalty: bool = False) -> PercentileRoll:
    """掷一次 d100。

    Args:
        rng: 随机源。
        extra: 追加的十位骰个数，即奖励骰或惩罚骰的个数。
        penalty: 追加的是惩罚骰而非奖励骰。
    """

    units = rng.randint(0, 9)
    tens = tuple(rng.randint(0, 9) for _ in range(extra + 1))
    results = [combine(one, units) for one in tens]

    return PercentileRoll(
        value=max(results) if penalty else min(results),
        units=units,
        tens=tens,
        penalty=penalty,
    )
