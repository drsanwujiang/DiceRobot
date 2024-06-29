import pytest

from app.enum import Role
from app.exceptions import OrderError
from . import BaseTest


class TestBot(BaseTest):
    def test_bot(self, client):
        self.wait_for_running()

        # Bot info
        message = self.build_group_message(".bot")
        result = self.post_message(client, message)

        message = self.build_group_message(".bot about")
        self.post_message(client, message)

        # Bot off
        message = self.build_group_message(".bot off")
        message.sender.role = Role.MEMBER

        with pytest.raises(OrderError):
            self.post_message(client, message)

        message.sender.role = Role.ADMIN
        self.post_message(client, message)

        message = self.build_group_message(".bot")  # Bot should not reply
        self.post_message(client, message)

        # Bot on
        message = self.build_group_message(".bot on")
        message.sender.role = Role.MEMBER

        with pytest.raises(OrderError):
            self.post_message(client, message)

        message.sender.role = Role.ADMIN
        self.post_message(client, message)

        message = self.build_group_message(".bot")  # Bot should reply
        self.post_message(client, message)

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
