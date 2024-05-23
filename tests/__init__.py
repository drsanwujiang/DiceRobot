import time

from fastapi.testclient import TestClient

from app.log import logger
from app.config import status, settings
from app.internal import MessageChainOrEvent
from app.internal.event import BotOnlineEvent
from app.internal.message import FriendMessage, GroupMessage
from app.internal.enum import AppStatus


class BaseTest:
    @staticmethod
    def post_message(client: TestClient, message_chain: MessageChainOrEvent) -> dict:
        settings["security"]["webhook"]["token"] = "test"

        return client.post(
            "/report",
            params={"token": "test"},
            json=message_chain.model_dump(by_alias=True)
        ).json()

    @staticmethod
    def build_bot_online_event() -> BotOnlineEvent:
        return BotOnlineEvent.model_validate({
            "type": "BotOnlineEvent",
            "qq": 10000
        })

    @staticmethod
    def build_friend_message(text: str) -> FriendMessage:
        return FriendMessage.model_validate({
            "type": "FriendMessage",
            "sender": {
                "id": 88888,
                "nickname": "Kaworu",
                "remark": "Kaworu"
            },
            "messageChain": [
                {
                    "type": "Source",
                    "id": 1,
                    "time": 1700000000
                },
                {
                    "type": "Plain",
                    "text": text
                }
            ]
        })

    @staticmethod
    def build_group_message(text: str) -> GroupMessage:
        return GroupMessage.model_validate({
            "sender": {
                "id": 88888,
                "memberName": "Kaworu",
                "specialTitle": "",
                "permission": "MEMBER",
                "joinTimestamp": 1600000000,
                "lastSpeakTimestamp": 1650000000,
                "muteTimeRemaining": 0,
                "group": {
                    "id": 12345,
                    "name": "Nerv",
                    "permission": "MEMBER"
                }
            },
            "messageChain": [
                {
                    "type": "Source",
                    "id": 1,
                    "time": 1700000000
                },
                {
                    "type": "Plain",
                    "text": text
                }
            ]
        })

    def wait_for_online(self, client: TestClient) -> None:
        logger.debug("Waiting for bot online")

        client.post(
            "/report",
            params={"token": "test"},
            json=self.build_bot_online_event().model_dump(by_alias=True)
        )

        time.sleep(1)

        assert status["app"] == AppStatus.RUNNING
