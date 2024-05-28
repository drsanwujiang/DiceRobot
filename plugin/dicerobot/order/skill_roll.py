import re
import random

from plugin import OrderPlugin
from app.exceptions import OrderInvalidError, OrderError


class SkillRoll(OrderPlugin):
    name = "dicerobot.skill_roll"
    display_name = "技能检定"
    description = "根据检定规则进行技能检定；加载指定的检定规则"
    version = "1.0.0"

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
        "result": "{&发送者}进行了检定：D100={&检定值}/{&技能值}，{&检定结果}",
        "result_with_reason": "由于{&检定原因}，{&发送者}进行了检定：D100={&检定值}/{&技能值}，{&检定结果}",
        "skill_invalid": "技能或属性值无法识别……",
        "rule_invalid": "检定规则无法识别……",
        "no_rule_matched": "没有匹配到检定等级……"
    }
    supported_reply_variables = [
        "检定原因",
        "检定值",
        "技能值",
        "检定结果"
    ]

    orders = [
        "ra", "检定", "技能检定",
        "rule", "检定规则"
    ]
    priority = 10

    _content_pattern = re.compile(r"^([1-9]\d*)?\s*([\S\s]*)$", re.I)
    _rule_pattern = re.compile(r"^[\d()><=+-/&| ]+$", re.I)

    def __call__(self) -> None:
        if self.order in ["ra", "检定", "技能检定"]:
            self.skill_roll()
        elif self.order in ["rule", "检定规则"]:
            self.show_rule()

    def skill_roll(self, _n: int = None) -> None:
        match = SkillRoll._content_pattern.fullmatch(self.order_content)
        skill = int(match.group(1)) if match.group(1) else None
        reason = match.group(2)

        if skill is None:
            raise OrderError(self.get_reply(key="skill_invalid"))

        check = random.randint(1, 100) if _n is None else _n
        result = None

        for level in self.get_chat_setting(key="rule")["levels"]:
            expression = level["condition"].replace("{&技能值}", str(skill)).replace("{&检定值}", str(check))

            # Check rule content
            if not SkillRoll._rule_pattern.fullmatch(expression):
                raise OrderError(self.get_reply(key="rule_invalid"))

            expression = expression.replace("&&", "and").replace("||", "or")

            try:
                eval_result = eval(expression)
            except Exception:
                raise OrderError(self.get_reply(key="rule_invalid"))

            # Check evaluation result
            if not isinstance(eval_result, bool):
                raise OrderError(self.get_reply(key="rule_invalid"))

            if eval_result:
                result = level["level"]
                break

        if result is None:
            raise OrderError(self.get_reply(key="no_rule_matched"))

        self.update_reply_variables({
            "检定原因": reason,
            "检定值": check,
            "技能值": skill,
            "检定结果": result
        })
        self.reply_to_sender(self.get_reply(key="result_with_reason" if reason else "result"))

    def show_rule(self) -> None:
        if self.order_content:
            raise OrderInvalidError()

        rule = self.get_chat_setting(key="rule")
        rule_content = f"当前使用的检定规则为：【{rule['name']}】\n{rule['description']}\n\n"
        rule_content += "\n".join([f"{level['level']}：{level['rule']}" for level in rule["levels"]])

        self.reply_to_sender(rule_content)
