import os

import pytest
from fastapi.testclient import TestClient

from app.config import plugin_settings
from app.enum import Role
from app.exceptions import OrderError, OrderInvalidError, OrderSuspiciousError, OrderRepetitionExceededError
from . import BaseTest


class TestOrder(BaseTest):
    def test_bot(self, client: TestClient):
        self.wait_for_running()

        # Bot info
        self.post_message(client, self.build_group_message(".bot"))
        self.post_message(client, self.build_group_message(".bot about"))

        # Bot off
        message = self.build_group_message(".bot off")
        message.sender.role = Role.MEMBER

        with pytest.raises(OrderError):
            self.post_message(client, message)

        message.sender.role = Role.ADMIN
        self.post_message(client, message)

        self.post_message(client, self.build_group_message(".bot"))  # Bot should not reply

        # Bot on
        message = self.build_group_message(".bot on")
        message.sender.role = Role.MEMBER

        with pytest.raises(OrderError):
            self.post_message(client, message)

        message.sender.role = Role.ADMIN
        self.post_message(client, message)

        self.post_message(client, self.build_group_message(".bot"))  # Bot should reply

        # Bot nickname
        message = self.build_group_message(".bot name Adam")
        message.sender.role = Role.MEMBER

        with pytest.raises(OrderError):
            self.post_message(client, message)

        message.sender.role = Role.ADMIN
        self.post_message(client, message)

        message = self.build_group_message(".bot name")
        message.sender.role = Role.MEMBER

        with pytest.raises(OrderError):
            self.post_message(client, message)

        message.sender.role = Role.ADMIN
        self.post_message(client, message)

    def test_dice(self, client: TestClient):
        self.wait_for_running()

        # Valid expressions
        self.post_message(client, self.build_group_message(".r"))
        self.post_message(client, self.build_group_message(".rd"))
        self.post_message(client, self.build_group_message(".rd100"))
        self.post_message(client, self.build_group_message(".r10d100k2"))
        self.post_message(client, self.build_group_message(".r(5d100+d30+666)*5-2+6d50k2x2+6X5 Some Reason"))
        self.post_message(client, self.build_group_message(".rd50Reason"))
        self.post_message(client, self.build_group_message(".rd50 Reason"))
        self.post_message(client, self.build_group_message(".rdReason"))
        self.post_message(client, self.build_group_message(".rd 50"))
        self.post_message(client, self.build_group_message(".rd#0"))
        self.post_message(client, self.build_group_message(".r10d100k2#3"))
        self.post_message(client, self.build_group_message(".r(5d100+d30+666)*5-2+6d50k2x2+6X5 Some Reason #3"))

        # Invalid order
        with pytest.raises(OrderRepetitionExceededError):
            self.post_message(client, self.build_group_message(".r#100"))

        # Invalid expressions
        with pytest.raises(OrderError):
            self.post_message(client, self.build_group_message(".r10d100kk2+5"))
            self.post_message(client, self.build_group_message(".r(10d100k2+5"))

    def test_hidden_dice(self, client: TestClient):
        self.wait_for_running()

        # In a group
        self.post_message(client, message := self.build_group_message(".rh"))

        # Not a friend
        message.user_id = 114514
        message.sender.user_id = 114514

        with pytest.raises(OrderError):
            self.post_message(client, message)

        # Not in a group
        with pytest.raises(OrderError):
            self.post_message(client, self.build_private_message(".rh"))

    def test_bonus_dice(self, client: TestClient):
        self.wait_for_running()

        # Valid expressions
        self.post_message(client, self.build_group_message(".rb"))
        self.post_message(client, self.build_group_message(".rb2"))
        self.post_message(client, self.build_group_message(".rbReason"))
        self.post_message(client, self.build_group_message(".r b3Reason"))
        self.post_message(client, self.build_group_message(".r b4 Reason"))
        self.post_message(client, self.build_group_message(".rb#0"))
        self.post_message(client, self.build_group_message(".rb2#3"))
        self.post_message(client, self.build_group_message(".r b4 Reason #3"))

        # Invalid order
        with pytest.raises(OrderRepetitionExceededError):
            self.post_message(client, self.build_group_message(".rb#100"))

        # Invalid expressions
        with pytest.raises(OrderError):
            self.post_message(client, self.build_group_message(".rb999"))

        # Suspicious expressions
        with pytest.raises(OrderSuspiciousError):
            self.post_message(client, self.build_group_message(".rb99999"))

    def test_penalty_dice(self, client: TestClient):
        self.wait_for_running()

        # Valid expressions
        self.post_message(client, self.build_group_message(".rp"))
        self.post_message(client, self.build_group_message(".rp2"))
        self.post_message(client, self.build_group_message(".rpReason"))
        self.post_message(client, self.build_group_message(".r p3Reason"))
        self.post_message(client, self.build_group_message(".r p4 Reason"))
        self.post_message(client, self.build_group_message(".rp#0"))
        self.post_message(client, self.build_group_message(".rp2#3"))
        self.post_message(client, self.build_group_message(".r p4 Reason #3"))

        # Invalid order
        with pytest.raises(OrderRepetitionExceededError):
            self.post_message(client, self.build_group_message(".rp#100"))

        # Invalid expressions
        with pytest.raises(OrderError):
            self.post_message(client, self.build_group_message(".rp999"))

        # Suspicious expressions
        with pytest.raises(OrderSuspiciousError):
            self.post_message(client, self.build_group_message(".rp99999"))

    def test_skill_roll(self, client: TestClient):
        self.wait_for_running()

        # Valid expressions
        self.post_message(client, self.build_group_message(".ra50"))
        self.post_message(client, self.build_group_message(".ra75#3"))

        # Invalid order
        with pytest.raises(OrderRepetitionExceededError):
            self.post_message(client, self.build_group_message(".ra#100"))

        # Suspicious expressions
        with pytest.raises(OrderSuspiciousError):
            self.post_message(client, self.build_group_message(".ra999999999"))

    def test_show_rule(self, client: TestClient):
        self.wait_for_running()

        self.post_message(client, self.build_group_message(".rule"))

    def test_chat(self, client: TestClient):
        if base_url := os.environ.get("TEST_AI_BASE_URL"):
            plugin_settings._plugin_settings["dicerobot.chat"]["base_url"] = base_url

        if api_key := os.environ.get("TEST_AI_API_KEY"):
            plugin_settings._plugin_settings["dicerobot.chat"]["api_key"] = api_key

        if model := os.environ.get("TEST_AI_MODEL"):
            plugin_settings._plugin_settings["dicerobot.chat"]["model"] = model

        self.wait_for_running()

        # Valid usage
        self.post_message(client, self.build_group_message(".chat Who are you?"))

        # Invalid usage
        with pytest.raises(OrderInvalidError):
            self.post_message(client, self.build_group_message(".chat"))
