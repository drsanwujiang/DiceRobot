from typing import Literal

from ...enum import ReportType, RequestType, GroupAddRequestSubType
from . import Report

__all__ = [
    "Request",
    "FriendAddRequest",
    "GroupAddRequest"
]


class Request(Report):
    post_type: Literal[ReportType.REQUEST] = ReportType.REQUEST
    request_type: RequestType
    user_id: int
    comment: str
    flag: str


class FriendAddRequest(Request):
    request_type: Literal[RequestType.FRIEND] = RequestType.FRIEND


class GroupAddRequest(Request):
    request_type: Literal[RequestType.GROUP] = RequestType.GROUP
    sub_type: GroupAddRequestSubType
    group_id: int
