from typing import Callable

from pydantic import field_validator

from .. import BaseModel


class RuleLevel(BaseModel):
    name: str
    description: str
    condition: Callable[[int, int], bool]

    @field_validator("condition", mode="before")
    def parse_condition(cls, condition: str) -> Callable[[int, int], bool]:
        if not isinstance(condition, str):
            raise ValueError("Condition must be a string")

        try:
            code = compile(f"lambda skill, roll: {condition}", "<string>", "eval")
            result = eval(code, {"__builtins__": {}}, {})  # Extremely restricted environment
            result(10, 10)  # Test with some values to ensure it's callable
            return result
        except Exception:
            raise ValueError("Condition invalid")


class RuleSet(BaseModel):
    id: str
    name: str
    description: str
    levels: list[RuleLevel]
