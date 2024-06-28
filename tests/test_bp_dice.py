import pytest

from app.exceptions import OrderSuspiciousError, OrderError
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
        message_chain = self.build_group_message(".rp")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".rp2")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".rpReason")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".r p3Reason")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".r p4 Reason")
        self.post_message(client, message_chain)

        # Invalid expressions
        message_chain = self.build_group_message(".rp999")

        with pytest.raises(OrderError):
            self.post_message(client, message_chain)

        # Suspicious expressions
        message_chain = self.build_group_message(".rp99999")

        with pytest.raises(OrderSuspiciousError):
            self.post_message(client, message_chain)
