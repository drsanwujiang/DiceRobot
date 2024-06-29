from typing import Literal

from ...enum import (
    ReportType, NoticeType, GroupAdminNoticeSubType, GroupDecreaseNoticeSubType, GroupIncreaseNoticeSubType,
    GroupBanNoticeSubType, NotifySubType
)
from . import Report

__all__ = [
    "Notice",
    "GroupUploadNotice",
    "GroupAdminNotice",
    "GroupDecreaseNotice",
    "GroupIncreaseNotice",
    "GroupBanNotice",
    "FriendAddNotice",
    "GroupRecallNotice",
    "FriendRecallNotice",
    "Notify"
]


class Notice(Report):
    post_type: Literal[ReportType.NOTICE] = ReportType.NOTICE
    notice_type: NoticeType
    user_id: int


class GroupUploadNotice(Notice):
    notice_type: Literal[NoticeType.GROUP_UPLOAD] = NoticeType.GROUP_UPLOAD
    group_id: int


class GroupAdminNotice(Notice):
    notice_type: Literal[NoticeType.GROUP_ADMIN] = NoticeType.GROUP_ADMIN
    sub_type: GroupAdminNoticeSubType
    group_id: int


class GroupDecreaseNotice(Notice):
    notice_type: Literal[NoticeType.GROUP_DECREASE] = NoticeType.GROUP_DECREASE
    sub_type: GroupDecreaseNoticeSubType
    group_id: int
    operator_id: int


class GroupIncreaseNotice(Notice):
    notice_type: Literal[NoticeType.GROUP_INCREASE] = NoticeType.GROUP_INCREASE
    sub_type: GroupIncreaseNoticeSubType
    group_id: int
    operator_id: int


class GroupBanNotice(Notice):
    notice_type: Literal[NoticeType.GROUP_BAN] = NoticeType.GROUP_BAN
    sub_type: GroupBanNoticeSubType
    group_id: int
    operator_id: int
    duration: int


class FriendAddNotice(Notice):
    notice_type: Literal[NoticeType.FRIEND_ADD] = NoticeType.FRIEND_ADD


class GroupRecallNotice(Notice):
    notice_type: Literal[NoticeType.GROUP_RECALL] = NoticeType.GROUP_RECALL
    group_id: int
    operator_id: int
    message_id: int


class FriendRecallNotice(Notice):
    notice_type: Literal[NoticeType.FRIEND_RECALL] = NoticeType.FRIEND_RECALL
    message_id: int


class Notify(Notice):
    notice_type: Literal[NoticeType.NOTIFY] = NoticeType.NOTIFY
    sub_type: NotifySubType
    group_id: int
