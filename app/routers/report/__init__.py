import json

from fastapi import APIRouter, Depends

from ...log import logger
from ...config import status
from ...auth import verify_token
from ...routers import Response
from ...internal.parser import parse_message_chain_or_event
from ...internal.message import Source, Quote, At, Plain, MessageChain
from ...internal.event import Event
from ...internal.dispatcher import dispatcher


router = APIRouter()


@router.post("/report", dependencies=[Depends(verify_token, use_cache=False)])
async def report(content: dict) -> Response:
    logger.debug("Report received, content: " + json.dumps(content))
    logger.info("Report started")

    try:
        message_chain_or_event = parse_message_chain_or_event(content)
    except ValueError:
        logger.info("Report skipped, message chain or event unparsable")
        return Response(code=1, message="Filtered")

    try:
        if isinstance(message_chain_or_event, MessageChain):
            handle_order(message_chain_or_event)
        elif isinstance(message_chain_or_event, Event):
            handle_event(message_chain_or_event)

        logger.info("Report finished")
    except RuntimeError:
        logger.info("Report filtered")
        return Response(code=1, message="Filtered")

    return Response()


def handle_order(message_chain: MessageChain) -> None:
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


def handle_event(event: Event) -> None:
    # Check handler status
    if not status["report"]["event"]:
        logger.info("Report skipped, event handler disabled")
        return

    dispatcher.dispatch_event(event)
