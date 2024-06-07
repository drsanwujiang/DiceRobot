import json

from fastapi import APIRouter, Depends

from ...log import logger
from ...auth import verify_webhook_token
from ...config import status
from ...dispatch import dispatcher
from ...enum import ApplicationStatus, MessageType
from ...exceptions import MessageInvalidError
from ...models import MessageChainOrEvent
from ...models.message import *
from ...models.event import *
from .. import Response

router = APIRouter()


@router.post("/report", dependencies=[Depends(verify_webhook_token, use_cache=False)])
async def message_report(content: dict) -> Response:
    logger.info("Webhook request received: message report")
    logger.debug("Webhook request content: " + json.dumps(content))

    message_chain_or_event = _parse_message_chain_or_event(content)

    try:
        logger.info("Message report started")

        if isinstance(message_chain_or_event, MessageChain):
            _handle_order(message_chain_or_event)
        elif isinstance(message_chain_or_event, Event):
            _handle_event(message_chain_or_event)

        logger.info("Message report finished")

        return Response()
    except RuntimeError:
        logger.info("Message report finished, message filtered")

        return Response(code=1, message="Message filtered")


def _parse_message_chain_or_event(message_chain_or_event: dict) -> MessageChain | Event:
    try:
        _message_chain_or_event = MessageChainOrEvent.model_validate(message_chain_or_event)

        return globals()[str(_message_chain_or_event.type.value)].model_validate(message_chain_or_event)
    except (KeyError, ValueError):
        raise MessageInvalidError


def _handle_order(message_chain: MessageChain) -> None:
    # Check app status
    if status.app != ApplicationStatus.RUNNING:
        logger.info("Message report skipped, DiceRobot not running")
        return

    # Check module status
    if not status.module.order:
        logger.info("Message report skipped, order module disabled")
        return

    plain_messages = []

    for message in message_chain.message_chain:
        match message.type:
            case MessageType.SOURCE | MessageType.QUOTE:
                continue
            case MessageType.AT:
                assert isinstance(message, At)

                if message.target == status.bot.id:
                    continue
                else:
                    logger.debug("Message to others detected")
                    raise RuntimeError
            case MessageType.PLAIN:
                assert isinstance(message, Plain)
                plain_messages.append(message.text.strip())
            case _:
                logger.debug("Unsupported message type detected")
                raise RuntimeError

    dispatcher.dispatch_order(message_chain, "\n".join(plain_messages))


def _handle_event(event: Event) -> None:
    # Check module status
    if not status.module.event:
        logger.info("Message report skipped, event module disabled")
        return

    dispatcher.dispatch_event(event)
