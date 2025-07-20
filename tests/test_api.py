from fastapi.testclient import TestClient

from . import BaseTest


class TestAPI(BaseTest):
    def test_admin_routes(self, client: TestClient):
        self.wait_for_running()

        # Get application status
        result = self.send_request(client, "get", "/status")
        assert result["app"] == 0

        # Set module status
        self.send_request(client, "post", "/status/module", {
            "order": True,
            "event": True
        })

        # Get application settings
        result = self.send_request(client, "get", "/settings/app")
        assert "dir" in result

        # Update application settings
        self.send_request(client, "patch", "/settings/app", {
            "dir": {
                "base": "base",
                "logs": "logs",
                "temp": "temp"
            }
        })

        plugin = "dicerobot.dice"

        # Get plugin list
        result = self.send_request(client, "get", "/plugins")
        assert plugin in result

        # Get plugin
        result = self.send_request(client, "get", f"/plugin/{plugin}")
        assert "display_name" in result

        # Get plugin settings
        result = self.send_request(client, "get", f"/plugin/{plugin}/settings")
        assert "enabled" in result

        # Update plugin settings
        self.send_request(client, "patch", f"/plugin/{plugin}/settings", {
            "enabled": False
        })

        # Reset plugin settings
        self.send_request(client, "post", f"/plugin/{plugin}/settings/reset")

    def test_qq_routes(self, client: TestClient):
        self.wait_for_running()

        # Get QQ status
        result = self.send_request(client, "get", "/qq/status")
        assert "version" in result

        # Get QQ settings
        result = self.send_request(client, "get", "/qq/settings")
        assert "dir" in result

        # Update QQ settings
        self.send_request(client, "patch", "/qq/settings", {
            "dir": {
                "base": "base",
                "config": "config"
            }
        })

    def test_napcat_routes(self, client: TestClient):
        self.wait_for_running()

        # Get NapCat status
        result = self.send_request(client, "get", "/napcat/status")
        assert "version" in result

        # Get NapCat settings
        result = self.send_request(client, "get", "/napcat/settings")
        assert "dir" in result

        # Update NapCat settings
        self.send_request(client, "patch", "/napcat/settings", {
            "dir": {
                "base": "base",
                "logs": "logs",
                "config": "config"
            }
        })
