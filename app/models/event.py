from typing import Literal

from ..enum import EventType
from . import MessageChainOrEvent

__all__ = [
    "Event",
    "BotOnlineEvent",
    "BotOfflineEventActive",
    "BotOfflineEventForce",
    "BotOfflineEventDropped",
    "BotReloginEvent",
    "NewFriendRequestEvent",
    "BotInvitedJoinGroupRequestEvent"
]


class Event(MessageChainOrEvent):
    type: EventType


class BotOnlineEvent(Event):
    type: Literal[EventType.BOT_ONLINE] = EventType.BOT_ONLINE
    qq: int


class BotOfflineEventActive(Event):
    type: Literal[EventType.BOT_OFFLINE_ACTIVE] = EventType.BOT_OFFLINE_ACTIVE
    qq: int


class BotOfflineEventForce(Event):
    type: Literal[EventType.BOT_OFFLINE_FORCE] = EventType.BOT_OFFLINE_FORCE
    qq: int


class BotOfflineEventDropped(Event):
    type: Literal[EventType.BOT_OFFLINE_DROPPED] = EventType.BOT_OFFLINE_DROPPED
    qq: int


class BotReloginEvent(Event):
    type: Literal[EventType.BOT_RELOGIN] = EventType.BOT_RELOGIN
    qq: int


class NewFriendRequestEvent(Event):
    type: Literal[EventType.NEW_FRIEND_REQUEST] = EventType.NEW_FRIEND_REQUEST
    event_id: int
    from_id: int
    group_id: int
    nick: str
    message: str


class BotInvitedJoinGroupRequestEvent(Event):
    type: Literal[EventType.BOT_INVITED_JOIN_GROUP_REQUEST] = EventType.BOT_INVITED_JOIN_GROUP_REQUEST
    event_id: int
    from_id: int
    group_id: int
    group_name: str
    nick: str
    message: str
