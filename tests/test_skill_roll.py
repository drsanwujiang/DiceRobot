import tests.base
from plugins.dicerobot.order.skill_roll import SkillRoll


def test_skill_roll():
    print()

    skill_roll = SkillRoll(order="ra", order_content="50")

    for n in range(1, 101):
        skill_roll.skill_roll(n)


def test_show_rule():
    print()

    skill_roll = SkillRoll(order="rule", order_content="")
    skill_roll.show_rule()
