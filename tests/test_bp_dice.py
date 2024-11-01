import pytest

from app.exceptions import OrderSuspiciousError, OrderRepetitionExceededError, OrderError
from . import BaseTest


class TestBPDice(BaseTest):
    def test_bonus_dice(self, client):
        self.wait_for_running()

        # Valid expressions
        message = self.build_group_message(".rb")
        self.post_message(client, message)

        message = self.build_group_message(".rb2")
        self.post_message(client, message)

        message = self.build_group_message(".rbReason")
        self.post_message(client, message)

        message = self.build_group_message(".r b3Reason")
        self.post_message(client, message)

        message = self.build_group_message(".r b4 Reason")
        self.post_message(client, message)

        message = self.build_group_message(".rb#0")
        self.post_message(client, message)

        message = self.build_group_message(".rb2#3")
        self.post_message(client, message)

        message = self.build_group_message(".r b4 Reason #3")
        self.post_message(client, message)

        # Invalid order
        message = self.build_group_message(".rb#100")

        with pytest.raises(OrderRepetitionExceededError):
            self.post_message(client, message)

        # Invalid expressions
        message = self.build_group_message(".rb999")

        with pytest.raises(OrderError):
            self.post_message(client, message)

        # Suspicious expressions
        message = self.build_group_message(".rb99999")

        with pytest.raises(OrderSuspiciousError):
            self.post_message(client, message)

    def test_penalty_dice(self, client):
        self.wait_for_running()

        # Valid expressions
        message = self.build_group_message(".rp")
        self.post_message(client, message)

        message = self.build_group_message(".rp2")
        self.post_message(client, message)

        message = self.build_group_message(".rpReason")
        self.post_message(client, message)

        message = self.build_group_message(".r p3Reason")
        self.post_message(client, message)

        message = self.build_group_message(".r p4 Reason")
        self.post_message(client, message)

        message = self.build_group_message(".rp#0")
        self.post_message(client, message)

        message = self.build_group_message(".rp2#3")
        self.post_message(client, message)

        message = self.build_group_message(".r p4 Reason #3")
        self.post_message(client, message)

        # Invalid order
        message = self.build_group_message(".rp#100")

        with pytest.raises(OrderRepetitionExceededError):
            self.post_message(client, message)

        # Invalid expressions
        message = self.build_group_message(".rp999")

        with pytest.raises(OrderError):
            self.post_message(client, message)

        # Suspicious expressions
        message = self.build_group_message(".rp99999")

        with pytest.raises(OrderSuspiciousError):
            self.post_message(client, message)
