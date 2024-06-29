import pytest

from app.exceptions import OrderError
from . import BaseTest


class TestHiddenDice(BaseTest):
    def test_hidden_dice(self, client):
        self.wait_for_running()

        # In group
        message = self.build_group_message(".rh")
        self.post_message(client, message)

        # Not friend
        message.user_id = 114514
        message.sender.user_id = 114514

        with pytest.raises(OrderError):
            self.post_message(client, message)

        # Not in group
        message = self.build_private_message(".rh")

        with pytest.raises(OrderError):
            self.post_message(client, message)
