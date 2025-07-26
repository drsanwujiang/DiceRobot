from fastapi.testclient import TestClient

from . import BaseTest


class TestAPI(BaseTest):
    def test_admin(self, client: TestClient):
        self.wait_for_running()

        # Get application status
        result = self.send_request(client, "get", "/status")
        assert result.code == 0 and result.data["app"] == 0

        # Set module status
        result = self.send_request(client, "post", "/status/module", {
            "order": True,
            "event": True
        })
        assert result.code == 0

        result = self.send_request(client, "post", "/status/module", {
            "order": False
        })
        assert result.code == -3

        result = self.send_request(client, "post", "/status/module", {
            "order": "Invalid"
        })
        assert result.code == -3

        # Update security settings
        result = self.send_request(client, "patch", "/settings/security", {
            "admin": {
                "password": "New password"
            }
        })
        assert result.code == 0

        result = self.send_request(client, "patch", "/settings/security", {
            "not_existed": "Not existed"
        })
        assert result.code == -3

        result = self.send_request(client, "patch", "/settings/security", {
            "admin": {
                "not_existed": "Not existed"
            }
        })
        assert result.code == -3

        # Get application settings
        result = self.send_request(client, "get", "/settings")
        assert result.code == 0 and "dir" in result.data

        # Update application settings
        result = self.send_request(client, "patch", "/settings", {
            "dir": {
                "base": "base",
                "logs": "logs",
                "temp": "temp"
            }
        })
        assert result.code == 0

        result = self.send_request(client, "patch", "/settings", {
            "not_existed": "Not existed"
        })
        assert result.code == -3

        result = self.send_request(client, "patch", "/settings", {
            "dir": {
                "not_existed": "Not existed"
            }
        })
        assert result.code == -3

        plugin = "dicerobot.dice"

        # Get plugin list
        result = self.send_request(client, "get", "/plugins")
        assert result.code == 0 and plugin in result.data

        # Get plugin
        result = self.send_request(client, "get", f"/plugin/{plugin}")
        assert result.code == 0 and "display_name" in result.data

        # Get plugin settings
        result = self.send_request(client, "get", f"/plugin/{plugin}/settings")
        assert result.code == 0 and "enabled" in result.data

        # Update plugin settings
        result = self.send_request(client, "patch", f"/plugin/{plugin}/settings", {
            "enabled": True,
            "max_count": 100,
            "max_surface": 1000
        })
        assert result.code == 0

        result = self.send_request(client, "patch", f"/plugin/{plugin}/settings", {
            "enabled": "Invalid"
        })
        assert result.code == -3

        result = self.send_request(client, "patch", f"/plugin/{plugin}/settings", {
            "not_existed": "Not existed"
        })
        assert result.code == -3

        result = self.send_request(client, "patch", f"/plugin/{plugin}/settings", {
            "enabled": True,
            "not_existed": "Not existed"
        })
        assert result.code == -3

        # Reset plugin settings
        result = self.send_request(client, "post", f"/plugin/{plugin}/settings/reset")
        assert result.code == 0

        # Get plugin replies
        result = self.send_request(client, "get", f"/plugin/{plugin}/replies")
        assert result.code == 0 and "result" in result.data

        # Update plugin replies
        result = self.send_request(client, "patch", f"/plugin/{plugin}/replies", {
            "result": "Result"
        })
        assert result.code == 0

        result = self.send_request(client, "patch", f"/plugin/{plugin}/replies", {
            "not_existed": "Not existed"
        })
        assert result.code == -3

        # Reset plugin replies
        result = self.send_request(client, "post", f"/plugin/{plugin}/replies/reset")
        assert result.code == 0

    def test_qq(self, client: TestClient):
        self.wait_for_running()

        # Get QQ status
        result = self.send_request(client, "get", "/qq/status")
        assert result.code == 0 and "version" in result.data

        # Get QQ settings
        result = self.send_request(client, "get", "/qq/settings")
        assert result.code == 0 and "dir" in result.data

        # Update QQ settings
        result = self.send_request(client, "patch", "/qq/settings", {
            "dir": {
                "base": "base",
                "config": "config"
            }
        })
        assert result.code == 0

        result = self.send_request(client, "patch", "/qq/settings", {
            "not_existed": "Not existed"
        })
        assert result.code == -3

        result = self.send_request(client, "patch", "/qq/settings", {
            "dir": {
                "not_existed": "Not existed"
            }
        })
        assert result.code == -3

    def test_napcat(self, client: TestClient):
        self.wait_for_running()

        # Get NapCat status
        result = self.send_request(client, "get", "/napcat/status")
        assert result.code == 0 and "version" in result.data

        # Get NapCat settings
        result = self.send_request(client, "get", "/napcat/settings")
        assert result.code == 0 and "dir" in result.data

        # Update NapCat settings
        result = self.send_request(client, "patch", "/napcat/settings", {
            "dir": {
                "base": "base",
                "logs": "logs",
                "config": "config"
            }
        })
        assert result.code == 0

        result = self.send_request(client, "patch", "/napcat/settings", {
            "account": "Invalid"
        })
        assert result.code == -3

        result = self.send_request(client, "patch", "/napcat/settings", {
            "not_existed": "Not existed"
        })
        assert result.code == -3

        result = self.send_request(client, "patch", "/napcat/settings", {
            "dir": {
                "not_existed": "Not existed"
            }
        })
        assert result.code == -3
