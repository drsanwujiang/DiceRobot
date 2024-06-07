import pytest

from app.exceptions import OrderError
from . import BaseTest


class TestDice(BaseTest):
    def test_dice(self, client):
        self.wait_for_online(client)

        # Valid expressions
        message_chain = self.build_group_message(".r")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".rd")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".rd100")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".r10d100k2")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".r(5d100+d30+666)*5-2+6d50k2x2+6X5 Some Reason")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".rd50Reason")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".rd50 Reason")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".rdReason")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".rd 50")
        self.post_message(client, message_chain)

        # Invalid expressions
        message_chain = self.build_group_message(".r10d100kk2+5")

        with pytest.raises(OrderError):
            self.post_message(client, message_chain)

        message_chain = self.build_group_message(".r(10d100k2+5")

        with pytest.raises(OrderError):
            self.post_message(client, message_chain)
