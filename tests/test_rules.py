"""检定规则加载的测试。

规则由机器人所有者手工编辑，难免写错，因此校验要在启动时拦下并指明是哪份文件的哪一级；
内置规则的判定行为也在此覆盖——它们只是写入文件的种子，运行时一律以文件为准。
"""

from __future__ import annotations

import json
from pathlib import Path

import pytest

from dicerobot.errors import ConfigurationError
from dicerobot.rules import DEFAULT_RULES, load_rules
from dicerobot.trpg.check import CheckRule, check


def write(directory: Path, name: str, document: dict[str, object]) -> Path:
    path = directory / f"{name}.json"
    path.write_text(json.dumps(document, ensure_ascii=False), encoding="utf-8")

    return path


def minimal(rule_id: str = "custom", *, levels: list[dict[str, str]] | None = None) -> dict[str, object]:
    return {
        "id": rule_id,
        "name": "自定义规则",
        "levels": levels
        if levels is not None
        else [
            {"name": "成功", "description": "骰值不大于技能值", "condition": "roll <= skill"},
            {"name": "失败", "description": "其余情况", "condition": "True"},
        ],
    }


class TestSeeding:
    def test_writes_the_builtin_rules_into_an_empty_directory(self, tmp_path: Path) -> None:
        rules = load_rules(tmp_path / "rules")

        assert set(rules) == set(DEFAULT_RULES)
        assert (tmp_path / "rules" / "coc7.json").exists()

    def test_restores_a_deleted_file_on_the_next_start(self, tmp_path: Path) -> None:
        """每次启动都补齐，误删一份下次启动即补回。"""

        load_rules(tmp_path)
        (tmp_path / "simple.json").unlink()

        assert "simple" in load_rules(tmp_path)

    def test_never_overwrites_an_existing_file(self, tmp_path: Path) -> None:
        """所有者改过的文件不能在启动时被写回默认内容。"""

        write(tmp_path, "coc7", minimal("coc7", levels=[{"name": "通过", "description": "", "condition": "True"}]))

        rules = load_rules(tmp_path)

        assert [level.name for level in rules["coc7"].levels] == ["通过"]


class TestValidation:
    def test_rejects_an_id_that_differs_from_the_filename(self, tmp_path: Path) -> None:
        """否则改了文件名却没改标识，会让人以为切换了规则。"""

        write(tmp_path, "house", minimal("coc7"))

        with pytest.raises(ConfigurationError, match="与文件名不一致"):
            load_rules(tmp_path)

    def test_rejects_an_unparsable_file(self, tmp_path: Path) -> None:
        (tmp_path / "broken.json").write_text("{", encoding="utf-8")

        with pytest.raises(ConfigurationError, match="无法解析"):
            load_rules(tmp_path)

    def test_rejects_an_unknown_field(self, tmp_path: Path) -> None:
        """拼错的字段若被忽略，改动会静默失效。"""

        document = minimal()
        document["levles"] = []
        write(tmp_path, "custom", document)

        with pytest.raises(ConfigurationError, match="无法解析"):
            load_rules(tmp_path)

    def test_rejects_a_forbidden_condition(self, tmp_path: Path) -> None:
        write(
            tmp_path,
            "custom",
            minimal(levels=[{"name": "成功", "description": "", "condition": "__import__('os').system('echo')"}]),
        )

        with pytest.raises(ConfigurationError, match="判定条件有问题"):
            load_rules(tmp_path)

    def test_rejects_a_rule_that_leaves_a_gap(self, tmp_path: Path) -> None:
        """缺少无条件匹配的等级时，玩家掷出那个点数才会报错，必须在启动时拦下。"""

        write(tmp_path, "custom", minimal(levels=[{"name": "成功", "description": "", "condition": "roll <= skill"}]))

        with pytest.raises(ConfigurationError, match="未覆盖"):
            load_rules(tmp_path)

    def test_reports_a_division_by_zero(self, tmp_path: Path) -> None:
        write(
            tmp_path,
            "custom",
            minimal(levels=[{"name": "成功", "description": "", "condition": "roll <= 100 // skill"}]),
        )

        with pytest.raises(ConfigurationError, match="出错"):
            load_rules(tmp_path)

    def test_an_unreachable_level_does_not_block_startup(self, tmp_path: Path) -> None:
        """等级顺序写反时某一级永远匹配不到，只记警告：所有者也可能是有意为之。"""

        write(
            tmp_path,
            "custom",
            minimal(
                levels=[
                    {"name": "成功", "description": "", "condition": "roll <= skill"},
                    {"name": "大成功", "description": "", "condition": "roll == 1"},
                    {"name": "失败", "description": "", "condition": "True"},
                ]
            ),
        )

        rules = load_rules(tmp_path)

        assert check(rules["custom"], skill=60, roll=1).name == "成功"


class TestBuiltinRules:
    @pytest.fixture
    def coc7(self, tmp_path: Path) -> CheckRule:
        return load_rules(tmp_path)["coc7"]

    @pytest.mark.parametrize(
        ("skill", "roll", "expected"),
        [
            (60, 1, "大成功"),
            (60, 12, "极难成功"),
            (60, 13, "困难成功"),
            (60, 30, "困难成功"),
            (60, 31, "成功"),
            (60, 60, "成功"),
            (60, 61, "失败"),
            (60, 95, "失败"),
            (60, 100, "大失败"),
            (49, 96, "大失败"),
            (50, 96, "失败"),
            (100, 100, "大失败"),
            (0, 1, "大成功"),
        ],
    )
    def test_coc7_levels(self, coc7: CheckRule, skill: int, roll: int, expected: str) -> None:
        assert check(coc7, skill=skill, roll=roll).name == expected

    def test_simple_only_distinguishes_success_and_failure(self, tmp_path: Path) -> None:
        simple = load_rules(tmp_path)["simple"]

        assert check(simple, skill=60, roll=1).name == "成功"
        assert check(simple, skill=60, roll=61).name == "失败"
