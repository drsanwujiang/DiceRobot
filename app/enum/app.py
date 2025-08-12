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


class DataType(str, Enum):
    RULE = "rule"
    DECK = "deck"
