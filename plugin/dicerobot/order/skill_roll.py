import re
import random

from plugin import OrderPlugin
from app.exceptions import OrderInvalidError, OrderSuspiciousError, OrderError


class SkillRoll(OrderPlugin):
    name = "dicerobot.skill_roll"
    display_name = "技能检定"
    description = "根据检定规则进行技能检定；加载指定的检定规则"
    version = "1.2.0"
    priority = 10
    max_repetition = 30
    orders = [
        "ra", "检定", "技能检定",
        "rule", "检定规则"
    ]
    default_chat_settings = {
        "rule": {
            "name": "COC 7 检定规则",
            "description": "COC 7 版规则书设定的检定规则",
            "levels": [
                {
                    "level": "大成功",
                    "rule": "骰出 1",
                    "condition": "{&检定值} == 1"
                },
                {
                    "level": "极难成功",
                    "rule": "骰出小于等于角色技能或属性值的五分之一（向下取整）",
                    "condition": "{&检定值} <= {&技能值} // 5"
                },
                {
                    "level": "困难成功",
                    "rule": "骰出小于等于角色技能或属性值的一半（向下取整）",
                    "condition": "{&检定值} <= {&技能值} // 2"
                },
                {
                    "level": "成功",
                    "rule": "骰出小于等于角色技能或属性值，也称为一般成功",
                    "condition": "{&检定值} <= {&技能值}"
                },
                {
                    "level": "失败",
                    "rule": "骰出大于角色技能或属性值",
                    "condition": "({&技能值} < 50 && {&检定值} < 96) || ({&技能值} >= 50 && {&检定值} < 100)"
                },
                {
                    "level": "大失败",
                    "rule": "骰出 100。若角色技能或属性值低于 50，则大于等于 96 的结果都是大失败",
                    "condition": "{&检定值} >= 96"
                }
            ]
        }
    }
    default_replies = {
        "result": "{&发送者}进行了检定：{&检定结果}",
        "result_with_reason": "由于{&检定原因}，{&发送者}进行了检定：{&检定结果}",
        "skill_invalid": "技能或属性值无法识别……",
        "rule_invalid": "检定规则无法识别……",
        "no_rule_matched": "没有匹配到检定等级……"
    }
    supported_reply_variables = [
        "检定原因",
        "检定结果"
    ]
    _content_pattern = re.compile(r"^([1-9]\d*)?\s*([\S\s]*)$", re.I)
    _rule_pattern = re.compile(r"^[\d()><=+-/&| ]+$", re.I)

    def __init__(self, *args, **kwargs) -> None:
        super().__init__(*args, **kwargs)

        self.skill_value = -1
        self.reason = ""

        self.roll_result = -1
        self.difficulty_level = ""
        self.full_result = ""

    async def __call__(self) -> None:
        if self.order in ["ra", "检定", "技能检定"]:
            self.check_repetition()
            self.parse_content()
            self.skill_roll()
            result = self.full_result

            if self.repetition > 1:
                result = f"\n{result}"

                for _ in range(self.repetition - 1):
                    self.skill_roll()
                    result += f"\n{self.full_result}"

            self.update_reply_variables({
                "检定原因": self.reason,
                "检定结果": result
            })
            await self.reply_to_sender(self.replies["result_with_reason" if self.reason else "result"])
        elif self.order in ["rule", "检定规则"]:
            self.max_repetition = 1
            self.check_repetition()
            await self.show_rule()

    def parse_content(self) -> None:
        match = self._content_pattern.fullmatch(self.order_content)

        # Check skill length
        if len(match.group(1) or "") > 5:
            raise OrderSuspiciousError

        self.skill_value = int(match.group(1)) if match.group(1) else self.skill_value
        self.reason = match.group(2)

        if self.skill_value < 0:
            raise OrderError(self.replies["skill_invalid"])

    def skill_roll(self) -> None:
        self.roll_result = random.randint(1, 100)
        difficulty_level = None

        for level in self.chat_settings["rule"]["levels"]:
            expression = level["condition"] \
                .replace("{&技能值}", str(self.skill_value)) \
                .replace("{&检定值}", str(self.roll_result))

            # Check rule content
            if not self._rule_pattern.fullmatch(expression):
                raise OrderError(self.replies["rule_invalid"])

            expression = expression.replace("&&", "and").replace("||", "or")

            try:
                eval_result = eval(expression)
            except:
                raise OrderError(self.replies["rule_invalid"])

            # Check evaluation result
            if not isinstance(eval_result, bool):
                raise OrderError(self.replies["rule_invalid"])

            if eval_result:
                difficulty_level = level["level"]
                break

        if difficulty_level is None:
            raise OrderError(self.replies["no_rule_matched"])

        self.difficulty_level = difficulty_level
        self.full_result = f"D100={self.roll_result}/{self.skill_value}，{self.difficulty_level}"

    async def show_rule(self) -> None:
        if self.order_content:
            raise OrderInvalidError

        rule = self.chat_settings["rule"]
        rule_content = f"当前使用的检定规则为：【{rule['name']}】\n{rule['description']}\n\n" + \
                       "\n".join([f"{level['level']}：{level['rule']}" for level in rule["levels"]])

        await self.reply_to_sender(rule_content)
