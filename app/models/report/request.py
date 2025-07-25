from typing import Literal

from ...enum import ReportType, RequestType, GroupRequestSubType
from . import Report

__all__ = [
    "Request",
    "FriendRequest",
    "GroupRequest"
]


class Request(Report):
    post_type: Literal[ReportType.REQUEST] = ReportType.REQUEST
    request_type: RequestType
    user_id: int
    comment: str
    flag: str


class FriendRequest(Request):
    request_type: Literal[RequestType.FRIEND] = RequestType.FRIEND


class GroupRequest(Request):
    request_type: Literal[RequestType.GROUP] = RequestType.GROUP
    sub_type: GroupRequestSubType
    group_id: int
