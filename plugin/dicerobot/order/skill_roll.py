import re
import random

from app.exceptions import OrderSuspiciousError, OrderError
from app.models.data import RuleSet
from ... import OrderPlugin


class SkillRoll(OrderPlugin):
    name = "dicerobot.skill_roll"
    display_name = "技能检定"
    description = "根据检定规则进行技能检定；加载指定的检定规则"
    version = "1.3.0"
    priority = 10
    max_repetition = 30
    orders = [
        "ra", "检定", "技能检定",
        "rule", "检定规则"
    ]
    default_chat_settings = {
        "rule": "coc7"
    }
    default_replies = {
        "result": "{&发送者}进行了检定：{&检定结果}",
        "result_with_reason": "由于{&检定原因}，{&发送者}进行了检定：{&检定结果}",
        "skill_invalid": "技能或属性值无法识别……",
        "rule_invalid": "检定规则无法识别……",
        "no_rule_matched": "没有匹配到检定等级……",
        "rule_not_found": "找不到这个检定规则诶……",
        "rule_set": "检定规则已设置为：【{&检定规则名称}】"
    }
    supported_reply_variables = [
        "检定原因",
        "检定结果",
        "检定规则名称"
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
            self.check_repetition(1)
            await self.show_set_rule()

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
        rule = self.context.data_manager.get_rule(self.chat_settings["rule"])  # type: RuleSet

        if rule is None:
            raise OrderError(self.replies["rule_invalid"])

        self.roll_result = random.randint(1, 100)

        for level in rule.levels:
            if level.condition(self.skill_value, self.roll_result):
                difficulty_level = level.name
                break
        else:
            raise OrderError(self.replies["no_rule_matched"])

        self.difficulty_level = difficulty_level
        self.full_result = f"D100={self.roll_result}/{self.skill_value}，{self.difficulty_level}"

    async def show_set_rule(self) -> None:
        if self.order_content:
            if self.order_content not in self.context.data_manager.list_rules():
                raise OrderError(self.replies["rule_not_found"])

            rule: RuleSet = self.context.data_manager.get_rule(self.order_content)
            self.plugin_settings["rule"] = rule.id
            self.save_plugin_settings()
            self.update_reply_variables({
                "检定规则名称": rule.name
            })
            await self.reply_to_sender(self.replies["rule_set"])
        else:
            rule = self.context.data_manager.get_rule(self.chat_settings["rule"])  # type: RuleSet
            rule_content = f"当前使用的检定规则为：【{rule.name}】\n{rule.description}\n\n" + \
                           "\n".join([f"{level.name}：{level.description}" for level in rule.levels])
            await self.reply_to_sender(rule_content)
