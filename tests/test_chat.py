from . import BaseTest


class TestChat(BaseTest):
    def test_chat(self, client, openai):
        self.wait_for_online(client)

        # Valid usage
        message_chain = self.build_group_message(".chat Who are you?")
        self.post_message(client, message_chain)

        # Invalid usage
        message_chain = self.build_group_message(".chat")
        self.post_message(client, message_chain)
