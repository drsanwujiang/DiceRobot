import json

from fastapi import APIRouter, Depends

from ...log import logger
from ...config import status
from ...auth import verify_token
from ...routers import Response
from ...dispatch import dispatcher
from ...enum import AppStatus
from ...exceptions import MessageInvalidError
from ...models import MessageChainOrEvent
from ...models.message import Source, Quote, At, Plain, MessageChain, FriendMessage, GroupMessage, TempMessage
from ...models.event import (
    Event, BotOnlineEvent, BotReloginEvent, BotOfflineEventActive, BotOfflineEventForce, BotOfflineEventDropped,
    NewFriendRequestEvent, BotInvitedJoinGroupRequestEvent
)

parsable_message_chains = [
    "FriendMessage", "GroupMessage", "TempMessage"
]

parsable_events = [
    "BotOnlineEvent", "BotReloginEvent", "BotOfflineEventActive", "BotOfflineEventForce", "BotOfflineEventDropped",
    "NewFriendRequestEvent", "BotInvitedJoinGroupRequestEvent"
]

router = APIRouter()


@router.post("/report", dependencies=[Depends(verify_token, use_cache=False)])
async def report(content: dict) -> Response:
    logger.debug("Report received, content: " + json.dumps(content))
    logger.info("Report started")

    try:
        message_chain_or_event = _parse_message_chain_or_event(content)
    except ValueError:
        logger.info("Report skipped, message chain or event unparsable")
        return Response(code=1, message="Filtered")

    try:
        if isinstance(message_chain_or_event, MessageChain):
            _handle_order(message_chain_or_event)
        elif isinstance(message_chain_or_event, Event):
            _handle_event(message_chain_or_event)

        logger.info("Report finished")
    except RuntimeError:
        logger.info("Report filtered")
        return Response(code=1, message="Filtered")

    return Response()


def _parse_message_chain_or_event(message_chain_or_event: dict) -> MessageChain | Event:
    try:
        _message_chain_or_event = MessageChainOrEvent.model_validate(message_chain_or_event)

        if _message_chain_or_event.type in parsable_message_chains:
            return globals()[_message_chain_or_event.type].model_validate(message_chain_or_event)
        elif _message_chain_or_event.type in parsable_events:
            return globals()[_message_chain_or_event.type].model_validate(message_chain_or_event)
        else:
            raise ValueError(f"Unparsable message chain or event type: {_message_chain_or_event.type}")
    except (KeyError, ValueError):
        raise MessageInvalidError()


def _handle_order(message_chain: MessageChain) -> None:
    # Check app status
    if status["app"] != AppStatus.RUNNING:
        logger.info("Report skipped, DiceRobot not running")
        return

    # Check handler status
    if not status["report"]["order"]:
        logger.info("Report skipped, order handler disabled")
        return

    message_content = ""

    for message in message_chain.message_chain:
        if isinstance(message, (Source, Quote)):
            continue
        elif isinstance(message, At):
            if message.target == status["bot"]["id"]:
                continue
            else:
                logger.debug("Message to others detected")
                raise RuntimeError("Message to others")
        elif isinstance(message, Plain):
            message_content += message.text
        else:
            logger.debug("Unsupported message type detected")
            raise RuntimeError("Unsupported message type")

    dispatcher.dispatch_order(message_chain, message_content.strip())


def _handle_event(event: Event) -> None:
    # Check handler status
    if not status["report"]["event"]:
        logger.info("Report skipped, event handler disabled")
        return

    dispatcher.dispatch_event(event)
