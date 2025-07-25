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
    META_EVENT = "meta_event"
    MESSAGE = "message"
    REQUEST = "request"
    NOTICE = "notice"


class MetaEventType(str, Enum):
    LIFECYCLE = "lifecycle"
    HEARTBEAT = "heartbeat"


class LifecycleMetaEventSubType(str, Enum):
    ENABLE = "enable"  # Unusable
    DISABLE = "disable"  # Unusable
    CONNECT = "connect"


class MessageType(str, Enum):
    PRIVATE = "private"
    GROUP = "group"


class PrivateMessageSubType(str, Enum):
    FRIEND = "friend"
    GROUP = "group"
    GROUP_SELF = "group_self"  # Unusable
    OTHER = "other"  # Unusable


class GroupMessageSubType(str, Enum):
    NORMAL = "normal"
    NOTICE = "notice"  # Unusable


class MessageSentType(str, Enum):
    ...


class RequestType(str, Enum):
    FRIEND = "friend"
    GROUP = "group"


class GroupRequestSubType(str, Enum):
    ADD = "add"
    INVITE = "invite"


class NoticeType(str, Enum):
    FRIEND_ADD = "friend_add"
    FRIEND_RECALL = "friend_recall"
    OFFLINE_FILE = "offline_file"  # Unusable
    CLIENT_STATUS = "client_status"  # Unusable
    GROUP_ADMIN = "group_admin"
    GROUP_BAN = "group_ban"
    GROUP_CARD = "group_card"
    GROUP_DECREASE = "group_decrease"
    GROUP_INCREASE = "group_increase"
    GROUP_RECALL = "group_recall"
    GROUP_UPLOAD = "group_upload"
    GROUP_MESSAGE_EMOJI_LIKE = "group_msg_emoji_like"
    ESSENCE = "group_essence"
    NOTIFY = "notify"


class GroupAdminNoticeSubType(str, Enum):
    SET = "set"
    UNSET = "unset"


class GroupBanNoticeSubType(str, Enum):
    BAN = "ban"
    LIFT_BAN = "lift_ban"


class GroupDecreaseNoticeSubType(str, Enum):
    LEAVE = "leave"
    KICK = "kick"
    KICK_ME = "kick_me"


class GroupIncreaseNoticeSubType(str, Enum):
    APPROVE = "approve"
    INVITE = "invite"


class EssenceNoticeSubType(str, Enum):
    ADD = "add"
    DELETE = "delete"


class NotifyNoticeSubType(str, Enum):
    POKE = "poke"
    INPUT_STATUS = "input_status"
    TITLE = "title"
    PROFILE_LIKE = "profile_like"


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
