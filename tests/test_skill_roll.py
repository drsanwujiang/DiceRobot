from . import BaseTest


class TestSkillRoll(BaseTest):
    def test_skill_roll(self, client):
        self.wait_for_online(client)

        message_chain = self.build_group_message(".ra50")

        for n in range(50):
            self.post_message(client, message_chain)

    def test_show_rule(self, client):
        self.wait_for_online(client)

        message_chain = self.build_group_message(".rule")
        self.post_message(client, message_chain)
