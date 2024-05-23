from . import BaseTest


class TestBPDice(BaseTest):
    def test_bonus_dice(self, client):
        self.wait_for_online(client)

        # Valid expressions
        message_chain = self.build_group_message(".rb")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".rb2")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".rbReason")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".r b3Reason")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".r b4 Reason")
        self.post_message(client, message_chain)

        # Invalid expressions
        message_chain = self.build_group_message(".rb101")
        self.post_message(client, message_chain)

    def test_penalty_dice(self, client):
        self.wait_for_online(client)

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
        message_chain = self.build_group_message(".rp101")
        self.post_message(client, message_chain)
