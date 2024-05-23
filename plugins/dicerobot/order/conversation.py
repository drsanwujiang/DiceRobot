from pydantic import TypeAdapter

from plugins import OrderPlugin
from plugins.dicerobot.order.chat import ChatCompletion, ChatCompletionRequest, ChatCompletionResponse
from app.exceptions import OrderInvalidError, OrderException
from app.internal.network import client


class Conversation(OrderPlugin):
    name = "dicerobot.conversation"
    display_name = "对话（GPT）"
    description = "使用 OpenAI 的 GPT 模型进行连续的聊天对话"
    version = "1.0.0"

    default_settings = {
        "domain": "api.openai.com",
        "api_key": "",
        "model": "gpt-4o"
    }
    default_replies = {
        "unusable": "请先设置神秘代码~",
        "new_conversation": "让我们开始吧~",
        "set_guidance": "【之后的对话将遵循以上设定】",
        "query_usage": "当前对话使用了{&当前使用量}个计费单位",
        "rate_limit_exceeded": "哎呀，思考不过来了呢……请重新再试一次吧~",
        "conversation_invalid": "唔……想不起来之前的对话了呢，让我们重新开始吧~"
    }
    supported_reply_variables = [
        "设定名称",
        "设定内容",
        "设定列表",
        "当前使用量"
    ]
    default_chat_settings = {
        "conversation": [],
        "tokens": 0
    }

    orders = [
        "conv", "对话",
        "guide", "设定",
    ]
    priority = 100

    def __call__(self) -> None:
        if not self.get_setting("api_key"):
            raise OrderException(self.get_reply("unusable"))

        if self.order in ["conv", "对话"]:
            if self.order_content in ["usage", "使用量"]:
                self.query_usage()
            else:
                self.conversation()
        elif self.order in ["guide", "设定"]:
            self.set_guidance()

    def conversation(self) -> None:
        if self.order_content:
            # Continue conversation
            conversation = self.load_conversation()
            conversation.append(ChatCompletion(
                role="user",
                content=self.order_content
            ))

            try:
                request = ChatCompletionRequest.model_validate({
                    "model": self.get_setting("model"),
                    "messages": conversation,
                    "user": f"{self.chat_type.value}-{self.chat_id}"
                })
            except ValueError:
                raise OrderInvalidError()

            result = client.post(
                "https://" + self.get_setting("domain") + "/v1/chat/completions",
                headers={
                    "Authorization": "Bearer " + self.get_setting("api_key")
                },
                json=request.model_dump(exclude_none=True),
                timeout=60
            ).json()

            try:
                response = ChatCompletionResponse.model_validate(result)
            except ValueError:
                raise OrderException(self.get_reply("rate_limit_exceeded"))

            conversation.append(response.choices[0].message)

            # Save conversation and tokens
            self.set_chat_setting("conversation", [completion.model_dump() for completion in conversation])
            self.set_chat_setting("tokens", response.usage.total_tokens)
            self.reply_to_sender(response.choices[0].message.content)
        else:
            # Clear conversation
            self.chat_settings.update(Conversation.default_chat_settings)
            self.reply_to_sender(self.get_reply("new_conversation"))

    def query_usage(self) -> None:
        self.update_reply_variables({
            "当前使用量": self.get_chat_setting("tokens")
        })
        self.reply_to_sender(self.get_reply("query_usage"))

    def set_guidance(self) -> None:
        if not self.order_content:
            raise OrderInvalidError()

        conversation = self.load_conversation()
        conversation.append(ChatCompletion(
            role="system",
            content=self.order_content
        ))

        # Save conversation
        self.set_chat_setting("conversation", [completion.model_dump() for completion in conversation])
        self.reply_to_sender(self.get_reply("set_guidance"))

    def load_conversation(self) -> list[ChatCompletion]:
        conversation = []

        if self.chat_settings["conversation"]:
            try:
                # Load saved conversation
                conversation = TypeAdapter(list[ChatCompletion]).validate_python(self.chat_settings["conversation"])
            except ValueError:
                # Clear conversation
                self.chat_settings.update(Conversation.default_chat_settings)
                raise OrderException(self.get_reply("conversation_invalid"))

        return conversation
