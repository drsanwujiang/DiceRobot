from enum import Enum


class ApplicationStatus(int, Enum):
    HOLDING = -1
    RUNNING = 0
    STARTED = 1


class MessageChainType(str, Enum):
    FRIEND = "FriendMessage"
    GROUP = "GroupMessage"
    TEMP = "TempMessage"


class MessageType(str, Enum):
    SOURCE = "Source"
    QUOTE = "Quote"
    AT = "At"
    PLAIN = "Plain"
    IMAGE = "Image"


class EventType(str, Enum):
    BOT_ONLINE = "BotOnlineEvent"
    BOT_OFFLINE_ACTIVE = "BotOfflineEventActive"
    BOT_OFFLINE_FORCE = "BotOfflineEventForce"
    BOT_OFFLINE_DROPPED = "BotOfflineEventDropped"
    BOT_RELOGIN = "BotReloginEvent"
    NEW_FRIEND_REQUEST = "NewFriendRequestEvent"
    BOT_INVITED_JOIN_GROUP_REQUEST = "BotInvitedJoinGroupRequestEvent"


class ChatType(str, Enum):
    FRIEND = "friend"
    GROUP = "group"
    TEMP = "temp"
