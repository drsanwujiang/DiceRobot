from . import BaseTest


class TestBot(BaseTest):
    def test_bot(self, client):
        self.wait_for_online(client)

        # Bot info
        message_chain = self.build_group_message(".bot")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".bot about")
        self.post_message(client, message_chain)

        # Bot on
        message_chain = self.build_group_message(".bot on")
        self.post_message(client, message_chain)

        message_chain.sender.permission = "ADMINISTRATOR"
        self.post_message(client, message_chain)

        # Bot off
        message_chain = self.build_group_message(".bot off")
        self.post_message(client, message_chain)

        message_chain.sender.permission = "ADMINISTRATOR"
        self.post_message(client, message_chain)

        # Bot nickname
        message_chain = self.build_group_message(".bot name Adam")
        self.post_message(client, message_chain)

        message_chain.sender.permission = "ADMINISTRATOR"
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".bot name")
        self.post_message(client, message_chain)

        message_chain.sender.permission = "ADMINISTRATOR"
        self.post_message(client, message_chain)
