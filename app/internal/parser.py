from pydantic import ValidationError

from ..exceptions import MessageInvalidException
from .message import *
from .event import *


parsable_messages = [
    "Source", "Quote", "At", "Plain", "Image"
]
parsable_message_chains = [
    "FriendMessage", "GroupMessage", "TempMessage"
]
parsable_events = [
    "BotOnlineEvent", "BotReloginEvent", "BotOfflineEventActive", "BotOfflineEventForce", "BotOfflineEventDropped",
    "NewFriendRequestEvent", "BotInvitedJoinGroupRequestEvent"
]


def parse_message_chain_or_event(message_change_or_event: dict) -> MessageChain | Event:
    try:
        if message_change_or_event["type"] in parsable_message_chains:
            message_change_or_event["messageChain"] = parse_messages(message_change_or_event["messageChain"])
            return globals()[message_change_or_event["type"]].model_validate(message_change_or_event)
        elif message_change_or_event["type"] in parsable_events:
            return globals()[message_change_or_event["type"]].model_validate(message_change_or_event)
        else:
            raise ValueError("Unparsable message chain or event type: " + message_change_or_event["type"])
    except (KeyError, ValidationError):
        raise MessageInvalidException()


def parse_messages(messages: list[dict]) -> list[Message]:
    parsed_messages: list[Message] = []

    for message in messages:
        try:
            if message["type"] in parsable_messages:
                parsed_messages.append(globals()[message["type"]].model_validate(message))
            else:
                raise ValueError("Unparsable message type: " + message["type"])
        except (KeyError, ValidationError):
            raise MessageInvalidException()

    return parsed_messages
