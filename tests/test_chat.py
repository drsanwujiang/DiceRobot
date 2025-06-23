import os

import pytest
from fastapi.testclient import TestClient

from app.config import plugin_settings
from app.exceptions import OrderInvalidError
from . import BaseTest


class TestChat(BaseTest):
    @staticmethod
    def preset_settings() -> None:
        if base_url := os.environ.get("TEST_AI_BASE_URL"):
            plugin_settings._plugin_settings["dicerobot.chat"]["base_url"] = base_url

        if api_key := os.environ.get("TEST_AI_API_KEY"):
            plugin_settings._plugin_settings["dicerobot.chat"]["api_key"] = api_key

        if model := os.environ.get("TEST_AI_MODEL"):
            plugin_settings._plugin_settings["dicerobot.chat"]["model"] = model

    def test_chat(self, client: TestClient):
        self.preset_settings()
        self.wait_for_running()

        # Valid usage
        message = self.build_group_message(".chat Who are you?")
        self.post_message(client, message)

        # Invalid usage
        message = self.build_group_message(".chat")

        with pytest.raises(OrderInvalidError):
            self.post_message(client, message)
