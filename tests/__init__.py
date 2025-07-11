import time

from loguru import logger
from fastapi.testclient import TestClient

from app.config import status
from app.enum import ApplicationStatus
from app.models.report.message import Message, PrivateMessage, GroupMessage


class BaseTest:
    @staticmethod
    def wait_for_running() -> None:
        if status.app == ApplicationStatus.RUNNING:
            return

        logger.debug("Waiting for DiceRobot running")

        time.sleep(2)
        assert status.app == ApplicationStatus.RUNNING

    @classmethod
    def post_message(cls, client: TestClient, message: Message) -> None:
        response = client.post("/report", json=message.model_dump(exclude_none=True))
        assert response.status_code == 204

    @staticmethod
    def build_private_message(text: str) -> PrivateMessage:
        return PrivateMessage.model_validate({
            "self_id": 99999,
            "user_id": 88888,
            "time": 1700000000,
            "message_id": -1234567890,
            "message_seq": -1234567890,
            "real_id": -1234567890,
            "message_type": "private",
            "sender": {
                "user_id": 88888,
                "nickname": "Kaworu",
                "card": ""
            },
            "raw_message": text,
            "font": 14,
            "sub_type": "friend",
            "message": [
                {
                    "data": {
                        "text": text
                    },
                    "type": "text"
                }
            ],
            "message_format": "array",
            "post_type": "message"
        })

    @staticmethod
    def build_group_message(text: str) -> GroupMessage:
        return GroupMessage.model_validate({
            "self_id": 99999,
            "user_id": 88888,
            "time": 1700000000,
            "message_id": -1234567890,
            "message_seq": -1234567890,
            "real_id": -1234567890,
            "message_type": "group",
            "sender": {
                "user_id": 88888,
                "nickname": "Kaworu",
                "card": "",
                "role": "owner"
            },
            "raw_message": text,
            "font": 14,
            "sub_type": "normal",
            "message": [
                {
                    "data": {
                        "text": text
                    },
                    "type": "text"
                }
            ],
            "message_format": "array",
            "post_type": "message",
            "group_id": 12345
        })

    @staticmethod
    def send_request(client: TestClient, method: str, path: str, data: dict = None) -> dict | None:
        response = client.request(method, path, json=data)
        assert response.status_code < 500
        result = response.json()

        logger.debug(f"Response content: {result}")

        assert result["code"] == 0
        return result["data"] if "data" in result else None
