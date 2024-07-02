from fastapi import APIRouter, Depends

from ..log import logger
from ..auth import verify_signature
from ..config import status
from ..dispatch import dispatcher
from ..enum import ApplicationStatus, ReportType, MessageType, NoticeType, RequestType, SegmentType
from ..exceptions import MessageInvalidError
from ..models.report import Report
from ..models.report.message import Message, PrivateMessage, GroupMessage
from ..models.report.notice import (
    Notice, GroupUploadNotice, GroupAdminNotice, GroupDecreaseNotice, GroupIncreaseNotice, GroupBanNotice,
    FriendAddNotice, GroupRecallNotice, FriendRecallNotice, Notify
)
from ..models.report.request import Request, FriendAddRequest, GroupAddRequest
from . import EmptyResponse

router = APIRouter()


@router.post("/report", dependencies=[Depends(verify_signature, use_cache=False)])
async def message_report(content: dict) -> EmptyResponse:
    logger.info("Webhook request received: report")
    logger.debug(f"Report content: {content}")

    try:
        report = Report.model_validate(content)

        logger.info("Report started")

        match report.post_type:
            case ReportType.MESSAGE:
                message = Message.model_validate(content)

                match message.message_type:
                    case MessageType.PRIVATE:
                        handle_message(PrivateMessage.model_validate(content))
                    case MessageType.GROUP:
                        handle_message(GroupMessage.model_validate(content))
            case ReportType.META_EVENT:
                pass
            case ReportType.NOTICE:
                notice = Notice.model_validate(content)

                match notice.notice_type:
                    case NoticeType.GROUP_UPLOAD:
                        handle_event(GroupUploadNotice.model_validate(content))
                    case NoticeType.GROUP_ADMIN:
                        handle_event(GroupAdminNotice.model_validate(content))
                    case NoticeType.GROUP_DECREASE:
                        handle_event(GroupDecreaseNotice.model_validate(content))
                    case NoticeType.GROUP_INCREASE:
                        handle_event(GroupIncreaseNotice.model_validate(content))
                    case NoticeType.GROUP_BAN:
                        handle_event(GroupBanNotice.model_validate(content))
                    case NoticeType.FRIEND_ADD:
                        handle_event(FriendAddNotice.model_validate(content))
                    case NoticeType.GROUP_RECALL:
                        handle_event(GroupRecallNotice.model_validate(content))
                    case NoticeType.FRIEND_RECALL:
                        handle_event(FriendRecallNotice.model_validate(content))
                    case NoticeType.NOTIFY:
                        handle_event(Notify.model_validate(content))
            case ReportType.REQUEST:
                request = Request.model_validate(content)

                match request.request_type:
                    case RequestType.FRIEND:
                        handle_event(FriendAddRequest.model_validate(content))
                    case RequestType.GROUP:
                        handle_event(GroupAddRequest.model_validate(content))

        logger.info("Report completed")
    except ValueError:
        logger.warning("Report finished, message invalid")
        raise MessageInvalidError
    except RuntimeError:
        logger.info("Report filtered")

    return EmptyResponse()


def handle_message(message: Message):
    # Check app status
    if status.app != ApplicationStatus.RUNNING:
        logger.info("Report skipped, DiceRobot not running")
        return

    # Check module status
    if not status.module.order:
        logger.info("Report skipped, order module disabled")
        return

    message_contents = []

    for segment in message.message:
        match segment.type:
            case SegmentType.AT:
                if segment.data.qq == status.bot.id:
                    continue
                else:
                    logger.debug("Message to others detected")
                    raise RuntimeError
            case SegmentType.TEXT:
                message_contents.append(segment.data.text.strip())
            case SegmentType.IMAGE:
                continue
            case _:
                logger.debug("Unsupported segment detected")
                raise RuntimeError

    dispatcher.dispatch_order(message, "\n".join(message_contents))


def handle_event(event: Notice | Request) -> None:
    # Check module status
    if not status.module.event:
        logger.info("Report skipped, event module disabled")
        return

    dispatcher.dispatch_event(event)
