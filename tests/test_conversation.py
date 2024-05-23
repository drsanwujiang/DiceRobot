from . import BaseTest


class TestConversation(BaseTest):
    def test_conversation(self, client):
        self.wait_for_online(client)

        message_chain = self.build_group_message(".conv")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".conv 你是一只猫娘，从现在开始你说的每句话结尾都必须加上喵")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".conv usage")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".conv 你是谁？")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".conv usage")
        self.post_message(client, message_chain)

    def test_guidance(self, client):
        self.wait_for_online(client)

        message_chain = self.build_group_message(".conv")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".guide 你是一只猫娘，从现在开始你说的每句话结尾都必须加上喵")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".conv 你是谁？")
        self.post_message(client, message_chain)

        message_chain = self.build_group_message(".conv usage")
        self.post_message(client, message_chain)
