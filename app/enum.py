from enum import Enum


class AppStatus(int, Enum):
    HOLDING = -1
    RUNNING = 0
    STARTED = 1


class ChatType(str, Enum):
    FRIEND = "friend"
    GROUP = "group"
    TEMP = "temp"
    OTHER = "other"
