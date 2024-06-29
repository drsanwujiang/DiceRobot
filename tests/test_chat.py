import pytest

from app.exceptions import OrderInvalidError
from . import BaseTest


class TestChat(BaseTest):
    def test_chat(self, client, openai):
        self.wait_for_running()

        # Valid usage
        message = self.build_group_message(".chat Who are you?")
        self.post_message(client, message)

        # Invalid usage
        message = self.build_group_message(".chat")

        with pytest.raises(OrderInvalidError):
            self.post_message(client, message)
