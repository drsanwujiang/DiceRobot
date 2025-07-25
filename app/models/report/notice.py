from typing import Any, Literal

from ...enum import (
    ReportType, NoticeType, GroupAdminNoticeSubType, GroupBanNoticeSubType, GroupDecreaseNoticeSubType,
    GroupIncreaseNoticeSubType, EssenceNoticeSubType, NotifyNoticeSubType
)
from . import Report

__all__ = [
    "Notice",
    "FriendAddNotice",
    "FriendRecallNotice",
    "GroupAdminNotice",
    "GroupBanNotice",
    "GroupCardNotice",
    "GroupDecreaseNotice",
    "GroupIncreaseNotice",
    "GroupRecallNotice",
    "GroupUploadNotice",
    "GroupMessageEmojiLikeNotice",
    "EssenceNotice",
    "NotifyNotice"
]


class Notice(Report):
    post_type: Literal[ReportType.NOTICE] = ReportType.NOTICE
    notice_type: NoticeType
    user_id: int


class FriendAddNotice(Notice):
    notice_type: Literal[NoticeType.FRIEND_ADD] = NoticeType.FRIEND_ADD


class FriendRecallNotice(Notice):
    notice_type: Literal[NoticeType.FRIEND_RECALL] = NoticeType.FRIEND_RECALL
    message_id: int


class GroupNotice(Notice):
    group_id: int


class GroupAdminNotice(GroupNotice):
    notice_type: Literal[NoticeType.GROUP_ADMIN] = NoticeType.GROUP_ADMIN
    sub_type: GroupAdminNoticeSubType


class GroupBanNotice(GroupNotice):
    notice_type: Literal[NoticeType.GROUP_BAN] = NoticeType.GROUP_BAN
    sub_type: GroupBanNoticeSubType
    operator_id: int
    duration: int


class GroupCardNotice(GroupNotice):
    notice_type: Literal[NoticeType.GROUP_CARD] = NoticeType.GROUP_CARD
    card_new: str
    card_old: str


class GroupDecreaseNotice(GroupNotice):
    notice_type: Literal[NoticeType.GROUP_DECREASE] = NoticeType.GROUP_DECREASE
    sub_type: GroupDecreaseNoticeSubType
    operator_id: int


class GroupIncreaseNotice(GroupNotice):
    notice_type: Literal[NoticeType.GROUP_INCREASE] = NoticeType.GROUP_INCREASE
    sub_type: GroupIncreaseNoticeSubType
    operator_id: int


class GroupRecallNotice(GroupNotice):
    notice_type: Literal[NoticeType.GROUP_RECALL] = NoticeType.GROUP_RECALL
    operator_id: int
    message_id: int


class GroupUploadNotice(GroupNotice):
    notice_type: Literal[NoticeType.GROUP_UPLOAD] = NoticeType.GROUP_UPLOAD
    file: Any


class GroupMessageEmojiLikeNotice(GroupNotice):
    notice_type: Literal[NoticeType.GROUP_MESSAGE_EMOJI_LIKE] = NoticeType.GROUP_MESSAGE_EMOJI_LIKE
    message_id: int
    likes: Any


class EssenceNotice(GroupNotice):
    notice_type: Literal[NoticeType.ESSENCE] = NoticeType.ESSENCE
    sub_type: EssenceNoticeSubType
    message_id: int
    sender_id: int
    operator_id: int


class NotifyNotice(GroupNotice):
    notice_type: Literal[NoticeType.NOTIFY] = NoticeType.NOTIFY
    sub_type: NotifyNoticeSubType
