from . import CamelizableModel


class Event(CamelizableModel):
    pass


class BotOnlineEvent(Event):
    type: str = "BotOnlineEvent"
    qq: int


class BotOfflineEventActive(Event):
    type: str = "BotOfflineEventActive"
    qq: int


class BotOfflineEventForce(Event):
    type: str = "BotOfflineEventForce"
    qq: int


class BotOfflineEventDropped(Event):
    type: str = "BotOfflineEventDropped"
    qq: int


class BotReloginEvent(Event):
    type: str = "BotReloginEvent"
    qq: int


class NewFriendRequestEvent(Event):
    type: str = "NewFriendRequestEvent"
    event_id: int
    from_id: int
    group_id: int
    nick: str
    message: str


class BotInvitedJoinGroupRequestEvent(Event):
    type: str = "BotInvitedJoinGroupRequestEvent"
    event_id: int
    from_id: int
    group_id: int
    group_name: str
    nick: str
    message: str
