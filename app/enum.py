from enum import Enum


class ApplicationStatus(int, Enum):
    HOLDING = -1
    RUNNING = 0
    STARTED = 1


class UpdateStatus(str, Enum):
    NONE = "none"
    CHECKING = "checking"
    DOWNLOADING = "downloading"
    INSTALLING = "installing"
    COMPLETED = "completed"
    FAILED = "failed"


class ChatType(str, Enum):
    FRIEND = "friend"
    GROUP = "group"
    TEMP = "temp"


class ReportType(str, Enum):
    MESSAGE = "message"
    META_EVENT = "meta_event"
    NOTICE = "notice"
    REQUEST = "request"


class MessageType(str, Enum):
    PRIVATE = "private"
    GROUP = "group"


class PrivateMessageSubType(str, Enum):
    FRIEND = "friend"
    GROUP = "group"
    OTHER = "other"


class GroupMessageSubType(str, Enum):
    NORMAL = "normal"
    ANONYMOUS = "anonymous"
    NOTICE = "notice"


class NoticeType(str, Enum):
    GROUP_UPLOAD = "group_upload"
    GROUP_ADMIN = "group_admin"
    GROUP_DECREASE = "group_decrease"
    GROUP_INCREASE = "group_increase"
    GROUP_BAN = "group_ban"
    FRIEND_ADD = "friend_add"
    GROUP_RECALL = "group_recall"
    FRIEND_RECALL = "friend_recall"
    NOTIFY = "notify"


class GroupAdminNoticeSubType(str, Enum):
    SET = "set"
    UNSET = "unset"


class GroupDecreaseNoticeSubType(str, Enum):
    LEAVE = "leave"
    KICK = "kick"
    KICK_ME = "kick_me"


class GroupIncreaseNoticeSubType(str, Enum):
    APPROVE = "approve"
    INVITE = "invite"


class GroupBanNoticeSubType(str, Enum):
    BAN = "ban"
    LIFT_BAN = "lift_ban"


class NotifySubType(str, Enum):
    POKE = "poke"
    LUCKY_KING = "lucky_king"
    HONOR = "honor"


class RequestType(str, Enum):
    FRIEND = "friend"
    GROUP = "group"


class GroupAddRequestSubType(str, Enum):
    ADD = "add"
    INVITE = "invite"


class MetaEventType(str, Enum):
    LIFECYCLE = "lifecycle"
    HEARTBEAT = "heartbeat"


class LifecycleMetaEventSubType(str, Enum):
    ENABLE = "enable"
    DISABLE = "disable"
    CONNECT = "connect"


class SegmentType(str, Enum):
    TEXT = "text"
    IMAGE = "image"
    AT = "at"
    REPLY = "reply"


class Sex(str, Enum):
    MALE = "male"
    FEMALE = "female"
    UNKNOWN = "unknown"


class Role(str, Enum):
    OWNER = "owner"
    ADMIN = "admin"
    MEMBER = "member"
