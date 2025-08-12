from loguru import logger
from fastapi import APIRouter, Depends

from ..dependencies import AppContextDep
from ..context import AppContext
from ..auth import verify_signature
from ..responses import EmptyResponse
from ..enum import ApplicationStatus, ReportType, MessageType, NoticeType, RequestType, SegmentType
from ..exceptions import MessageInvalidError
from ..models.report import Report
from ..models.report.message import (
    Message, PrivateMessage, GroupMessage
)
from ..models.report.request import (
    Request, FriendRequest, GroupRequest
)
from ..models.report.notice import (
    Notice, FriendAddNotice, FriendRecallNotice, GroupUploadNotice, GroupAdminNotice, GroupBanNotice, GroupCardNotice,
    GroupDecreaseNotice, GroupIncreaseNotice, GroupRecallNotice, GroupMessageEmojiLikeNotice, EssenceNotice,
    NotifyNotice
)

REPORTS: dict[tuple, type[Report]] = {
    (ReportType.MESSAGE, MessageType.PRIVATE): PrivateMessage,
    (ReportType.MESSAGE, MessageType.GROUP): GroupMessage,
    (ReportType.REQUEST, RequestType.FRIEND): FriendRequest,
    (ReportType.REQUEST, RequestType.GROUP): GroupRequest,
    (ReportType.NOTICE, NoticeType.FRIEND_ADD): FriendAddNotice,
    (ReportType.NOTICE, NoticeType.FRIEND_RECALL): FriendRecallNotice,
    (ReportType.NOTICE, NoticeType.GROUP_ADMIN): GroupAdminNotice,
    (ReportType.NOTICE, NoticeType.GROUP_BAN): GroupBanNotice,
    (ReportType.NOTICE, NoticeType.GROUP_CARD): GroupCardNotice,
    (ReportType.NOTICE, NoticeType.GROUP_DECREASE): GroupDecreaseNotice,
    (ReportType.NOTICE, NoticeType.GROUP_INCREASE): GroupIncreaseNotice,
    (ReportType.NOTICE, NoticeType.GROUP_RECALL): GroupRecallNotice,
    (ReportType.NOTICE, NoticeType.GROUP_UPLOAD): GroupUploadNotice,
    (ReportType.NOTICE, NoticeType.GROUP_MESSAGE_EMOJI_LIKE): GroupMessageEmojiLikeNotice,
    (ReportType.NOTICE, NoticeType.ESSENCE): EssenceNotice,
    (ReportType.NOTICE, NoticeType.NOTIFY): NotifyNotice
}
IGNORED_TYPES = {
    (ReportType.META_EVENT, None),
    (ReportType.NOTICE, NoticeType.OFFLINE_FILE),
    (ReportType.NOTICE, NoticeType.CLIENT_STATUS),
}
router = APIRouter()


@router.post("/report", dependencies=[Depends(verify_signature, use_cache=False)])
async def message_report(content: dict, context: AppContextDep) -> EmptyResponse:
    logger.info("API request received: Webhook report")
    logger.debug(f"Report content: {content}")

    post_type = content.get("post_type")
    sub_type = content.get("message_type") or content.get("request_type") or content.get("notice_type")

    if any([
        (post_type, sub_type) in IGNORED_TYPES,
        (post_type, None) in IGNORED_TYPES,
        (post_type, sub_type) not in REPORTS,
    ]):
        logger.debug(f"Report \"{post_type} ({sub_type})\" ignored")
        return EmptyResponse()

    try:
        logger.info(f"Report \"{post_type} ({sub_type})\" started")
        report = REPORTS[(post_type, sub_type)].model_validate(content)

        if isinstance(report, Message):
            await handle_message(report, context)
        elif isinstance(report, (Notice, Request)):
            await handle_event(report, context)

        logger.info("Report completed")
    except ValueError:
        logger.warning("Report finished, message invalid")
        raise MessageInvalidError
    except RuntimeError as e:
        logger.info(e)

    return EmptyResponse()


async def handle_message(message: Message, context: AppContext):
    # Check app status
    if context.status.app != ApplicationStatus.RUNNING:
        raise RuntimeError("Report skipped, application not running")

    # Check module status
    if not context.status.module.order:
        raise RuntimeError("Report skipped, order module disabled")

    message_contents = []

    for segment in message.message:
        match segment.type:
            case SegmentType.AT:
                if segment.data.qq == context.status.bot.id:
                    continue
                else:
                    raise RuntimeError("Report filtered, message targeted to others detected")
            case SegmentType.TEXT:
                message_contents.append(segment.data.text.strip())
            case SegmentType.IMAGE:
                continue
            case _:
                raise RuntimeError(f"Report filtered, unsupported segment type \"{segment.type.value}\" detected")

    await context.dispatch_manager.dispatch_order(message, "\n".join(message_contents).strip())


async def handle_event(event: Notice | Request, context: AppContext) -> None:
    # Check module status
    if not context.status.module.event:
        raise RuntimeError("Report skipped, event module disabled")

    await context.dispatch_manager.dispatch_event(event)
