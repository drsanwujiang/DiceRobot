from loguru import logger
from fastapi import APIRouter, Depends

from ..auth import verify_signature
from ..config import status
from ..dispatch import dispatcher
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

router = APIRouter()


@router.post("/report", dependencies=[Depends(verify_signature, use_cache=False)])
async def message_report(content: dict) -> EmptyResponse:
    logger.info("Webhook request received: report")
    logger.debug(f"Report content: {content}")

    try:
        report = Report.model_validate(content)

        logger.info("Report started")

        match report.post_type:
            case ReportType.META_EVENT:
                # Ignore meta event
                pass
            case ReportType.MESSAGE:
                message = Message.model_validate(content)

                match message.message_type:
                    case MessageType.PRIVATE:
                        await handle_message(PrivateMessage.model_validate(content))
                    case MessageType.GROUP:
                        await handle_message(GroupMessage.model_validate(content))
            case ReportType.REQUEST:
                request = Request.model_validate(content)

                match request.request_type:
                    case RequestType.FRIEND:
                        await handle_event(FriendRequest.model_validate(content))
                    case RequestType.GROUP:
                        await handle_event(GroupRequest.model_validate(content))
            case ReportType.NOTICE:
                notice = Notice.model_validate(content)

                match notice.notice_type:
                    case NoticeType.FRIEND_ADD:
                        await handle_event(FriendAddNotice.model_validate(content))
                    case NoticeType.FRIEND_RECALL:
                        await handle_event(FriendRecallNotice.model_validate(content))
                    case NoticeType.OFFLINE_FILE:
                        # Ignore offline file notice
                        pass
                    case NoticeType.CLIENT_STATUS:
                        # Ignore client status notice
                        pass
                    case NoticeType.GROUP_ADMIN:
                        await handle_event(GroupAdminNotice.model_validate(content))
                    case NoticeType.GROUP_BAN:
                        await handle_event(GroupBanNotice.model_validate(content))
                    case NoticeType.GROUP_CARD:
                        await handle_event(GroupCardNotice.model_validate(content))
                    case NoticeType.GROUP_DECREASE:
                        await handle_event(GroupDecreaseNotice.model_validate(content))
                    case NoticeType.GROUP_INCREASE:
                        await handle_event(GroupIncreaseNotice.model_validate(content))
                    case NoticeType.GROUP_RECALL:
                        await handle_event(GroupRecallNotice.model_validate(content))
                    case NoticeType.GROUP_UPLOAD:
                        await handle_event(GroupUploadNotice.model_validate(content))
                    case NoticeType.GROUP_MESSAGE_EMOJI_LIKE:
                        await handle_event(GroupMessageEmojiLikeNotice.model_validate(content))
                    case NoticeType.ESSENCE:
                        await handle_event(EssenceNotice.model_validate(content))
                    case NoticeType.NOTIFY:
                        await handle_event(NotifyNotice.model_validate(content))

        logger.info("Report completed")
    except ValueError:
        logger.warning("Report finished, message invalid")
        raise MessageInvalidError
    except RuntimeError:
        logger.info("Report filtered")

    return EmptyResponse()


async def handle_message(message: Message):
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

    await dispatcher.dispatch_order(message, "\n".join(message_contents))


async def handle_event(event: Notice | Request) -> None:
    # Check module status
    if not status.module.event:
        logger.info("Report skipped, event module disabled")
        return

    await dispatcher.dispatch_event(event)
