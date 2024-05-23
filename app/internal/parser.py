from ..exceptions import MessageInvalidError
from . import MessageChainOrEvent
from .message import MessageChain, FriendMessage, GroupMessage, TempMessage
from .event import (
    Event, BotOnlineEvent, BotOfflineEventActive, BotOfflineEventForce, BotOfflineEventDropped, BotReloginEvent,
    NewFriendRequestEvent, BotInvitedJoinGroupRequestEvent
)


parsable_message_chains = [
    "FriendMessage", "GroupMessage", "TempMessage"
]
parsable_events = [
    "BotOnlineEvent", "BotReloginEvent", "BotOfflineEventActive", "BotOfflineEventForce", "BotOfflineEventDropped",
    "NewFriendRequestEvent", "BotInvitedJoinGroupRequestEvent"
]


def parse_message_chain_or_event(message_chain_or_event: dict) -> MessageChain | Event:
    try:
        _message_chain_or_event = MessageChainOrEvent.model_validate(message_chain_or_event)

        if _message_chain_or_event.type in parsable_message_chains:
            return globals()[_message_chain_or_event.type].model_validate(message_chain_or_event)
        elif _message_chain_or_event.type in parsable_events:
            return globals()[_message_chain_or_event.type].model_validate(message_chain_or_event)
        else:
            raise ValueError(f"Unparsable message chain or event type: {_message_chain_or_event.type}")
    except (KeyError, ValueError):
        raise MessageInvalidError()
