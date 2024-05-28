from . import BaseTest


class TestHiddenDice(BaseTest):
    def test_hidden_dice(self, client):
        self.wait_for_online(client)

        # In group
        message_chain = self.build_group_message(".rh")
        self.post_message(client, message_chain)

        message_chain.sender.id = 99999
        self.post_message(client, message_chain)

        # Not in group
        message_chain = self.build_friend_message(".rh")
        self.post_message(client, message_chain)
