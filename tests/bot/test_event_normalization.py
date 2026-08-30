"""事件归一化的测试。"""

from __future__ import annotations

from datetime import UTC, datetime
from typing import Any

import pytest

from dicerobot.bot.message import IncomingMessage, normalize_event, normalize_message
from dicerobot.enums import Scene
from dicerobot.qq.enums import EventType
from dicerobot.qq.schemas import Payload

RECEIVED_AT = datetime(2026, 1, 1, tzinfo=UTC)


def payload(event_type: str, data: dict[str, Any], *, event_id: str | None = "EVENT_1") -> Payload:
    return Payload(op=0, id=event_id, t=event_type, d=data)


def group_message(content: str) -> IncomingMessage | None:
    return normalize_message(
        payload(
            "GROUP_AT_MESSAGE_CREATE",
            {"id": "MSG_1", "group_openid": "G1", "author": {"member_openid": "U1"}, "content": content},
        ),
        received_at=RECEIVED_AT,
    )


class TestContentCleanup:
    def test_strips_the_mention_of_the_bot(self) -> None:
        """平台不剥离 @ 机器人的标记，正文形如 <@openid> .r，且 openid 不是数字。"""

        message = group_message("<@F0A70CF8E9C1CB6E46614D877FBBDBED> .r")

        assert message is not None
        assert message.content == ".r"

    def test_strips_mentions_of_other_members(self) -> None:
        message = group_message("<@F0A70CF8E9C1CB6E46614D877FBBDBED> .ra 侦查 <@EC89EBEC3CF575BD5832927E4B689CAC>")

        assert message is not None
        assert "<@" not in message.content
        assert message.content.startswith(".ra 侦查")

    def test_strips_the_legacy_numeric_form(self) -> None:
        """频道时代的 <@!数字> 形式仍应剥离。"""

        message = group_message("<@!123456789> .ping")

        assert message is not None
        assert message.content == ".ping"

    def test_keeps_content_without_a_mention(self) -> None:
        message = group_message(".r 3d6+2 侦查")

        assert message is not None
        assert message.content == ".r 3d6+2 侦查"


class TestGroupRobotEvents:
    def test_normalizes_being_added_to_a_group(self) -> None:
        event = normalize_event(
            payload("GROUP_ADD_ROBOT", {"group_openid": "G1", "op_member_openid": "U1"}),
            received_at=RECEIVED_AT,
        )

        assert event is not None
        assert event.type is EventType.GROUP_ADD_ROBOT
        assert event.scene is Scene.GROUP
        assert event.scene_id == "G1"
        assert event.operator_id == "U1"

    def test_operator_is_optional(self) -> None:
        event = normalize_event(payload("GROUP_ADD_ROBOT", {"group_openid": "G1"}), received_at=RECEIVED_AT)

        assert event is not None
        assert event.operator_id is None

    def test_raw_data_is_preserved(self) -> None:
        """归一化未覆盖的字段仍应能被插件读到。"""

        event = normalize_event(
            payload("GROUP_ADD_ROBOT", {"group_openid": "G1", "timestamp": 1730000000}),
            received_at=RECEIVED_AT,
        )

        assert event is not None
        assert event.data["timestamp"] == 1730000000


class TestFriendEvents:
    def test_normalizes_being_added_as_a_friend(self) -> None:
        event = normalize_event(payload("FRIEND_ADD", {"openid": "U9"}), received_at=RECEIVED_AT)

        assert event is not None
        assert event.scene is Scene.C2C
        assert event.scene_id == "U9"
        assert event.operator_id == "U9"


class TestReplyTarget:
    def test_event_replies_carry_the_event_id(self) -> None:
        """事件的被动回复凭据是 event_id 而非 msg_id。"""

        event = normalize_event(payload("GROUP_ADD_ROBOT", {"group_openid": "G1"}), received_at=RECEIVED_AT)

        assert event is not None

        target = event.reply_target
        assert target.event_id == "EVENT_1"
        assert target.msg_id is None
        assert target.received_at == RECEIVED_AT

    def test_message_replies_carry_the_message_id(self) -> None:
        message = normalize_message(
            payload(
                "GROUP_AT_MESSAGE_CREATE",
                {"id": "MSG_1", "group_openid": "G1", "author": {"member_openid": "U1"}, "content": ".ping"},
            ),
            received_at=RECEIVED_AT,
        )

        assert message is not None

        target = message.reply_target
        assert target.msg_id == "MSG_1"
        assert target.event_id is None


class TestRejections:
    def test_message_events_are_not_normalized_as_events(self) -> None:
        data = {"id": "MSG_1", "group_openid": "G1", "author": {"member_openid": "U1"}, "content": ".ping"}

        assert normalize_event(payload("GROUP_AT_MESSAGE_CREATE", data), received_at=RECEIVED_AT) is None

    def test_event_without_an_id_is_skipped(self) -> None:
        """没有事件 ID 就无法被动回复，处理它没有意义。"""

        assert (
            normalize_event(payload("GROUP_ADD_ROBOT", {"group_openid": "G1"}, event_id=None), received_at=RECEIVED_AT)
            is None
        )

    @pytest.mark.parametrize("event_type", ["UNKNOWN_EVENT", "GROUP_MSG_REJECT"])
    def test_unsupported_event_types_are_skipped(self, event_type: str) -> None:
        assert normalize_event(payload(event_type, {}), received_at=RECEIVED_AT) is None

    def test_payload_without_a_type_is_skipped(self) -> None:
        assert normalize_event(Payload(op=0, id="E1"), received_at=RECEIVED_AT) is None
