from .message import Message, Plain, MessageChain, FriendMessage, GroupMessage, TempMessage
from .network import send_friend_message, send_group_message, send_temp_message


def reply_to_sender(source_message_chain: MessageChain, reply_messages: str | list[Message]) -> None:
    if isinstance(reply_messages, str):
        reply_messages = [Plain.model_validate({"type": "Plain", "text": reply_messages})]

    if type(source_message_chain) is FriendMessage:
        send_friend_message({
            "target": source_message_chain.sender.id,
            "message_chain": reply_messages
        })
    elif type(source_message_chain) is GroupMessage:
        send_group_message({
            "target": source_message_chain.sender.group.id,
            "message_chain": reply_messages
        })
    elif type(source_message_chain) is TempMessage:
        send_temp_message({
            "qq": source_message_chain.sender.id,
            "group": source_message_chain.sender.group.id,
            "message_chain": reply_messages
        })
    else:
        raise RuntimeError("Invalid message chain type")