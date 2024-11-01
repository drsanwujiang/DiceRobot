import pytest

from app.exceptions import OrderSuspiciousError, OrderRepetitionExceededError
from . import BaseTest


class TestSkillRoll(BaseTest):
    def test_skill_roll(self, client):
        self.wait_for_running()

        # Valid expressions
        message = self.build_group_message(".ra50")
        self.post_message(client, message)

        message = self.build_group_message(".ra75#3")
        self.post_message(client, message)

        # Invalid order
        message = self.build_group_message(".ra#100")

        with pytest.raises(OrderRepetitionExceededError):
            self.post_message(client, message)

        # Suspicious expressions
        message = self.build_group_message(".ra999999999")

        with pytest.raises(OrderSuspiciousError):
            self.post_message(client, message)

    def test_show_rule(self, client):
        self.wait_for_running()

        message_chain = self.build_group_message(".rule")
        self.post_message(client, message_chain)
