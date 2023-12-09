from pydantic import TypeAdapter, ValidationError

from app.exceptions import OrderInvalidError, OrderException
from app.internal.network import client
from plugins import OrderPlugin
from plugins.dicerobot.order.chat import ChatCompletion, ChatCompletionRequest, ChatCompletionResponse


class Conversation(OrderPlugin):
    name = "dicerobot.conversation"
    display_name = "对话（GPT）"
    description = "使用 OpenAI 的 GPT 模型进行连续的聊天对话"
    version = "1.0.0"

    default_settings = {
        "domain": "api.openai.com",
        "api_key": "",
        "model": "gpt-4",
        "max_saved_guidance": 10
    }
    default_replies = {
        "new_conversation": "让我们开始吧~",
        "set_guidance": "之后的对话将遵循以上设定",
        "load_guidance": "已读取设定【{&设定名称}】，之后的对话将遵循这个设定",
        "query_usage": "当前对话使用了{&当前使用量}个计费单位",
        "rate_limit_exceeded": "哎呀，思考不过来了呢……请重新再试一次吧~",
        "conversation_invalid": "唔……想不起来之前的对话了呢，让我们重新开始吧~",
        "guidance_not_found": "找不到这个设定呢……"
    }
    supported_reply_variables = [
        "设定名称",
        "设定内容",
        "设定列表",
        "当前使用量"
    ]
    default_chat_settings = {
        "conversation": [],
        "tokens": 0,
        "saved_guidance": []
    }

    orders = [
        "convo", "对话",
        "guide", "设定",
        # "load_guide", "读取设定", "加载设定"
    ]
    priority = 100

    def __call__(self) -> None:
        if self.order in ["convo", "对话"]:
            if self.order_content in ["usage", "使用量"]:
                self.query_usage()
            else:
                self.conversation()
        elif self.order in ["guide", "设定"]:
            self.set_guidance()
        # elif self.order in ["load_guide", "读取设定", "加载设定"]:
        #     self.load_guidance()

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
                    "model": self.settings["model"],
                    "messages": conversation,
                    "user": f"{self.chat_type.value}-{self.chat_id}"
                })
            except ValidationError:
                raise OrderInvalidError()

            result = client.post(
                "https://" + self.settings["domain"] + "/v1/chat/completions",
                headers={
                    "Authorization": "Bearer " + self.settings["api_key"]
                },
                json=request.model_dump(exclude_none=True),
                timeout=60
            ).json()

            try:
                response = ChatCompletionResponse.model_validate(result)
            except ValidationError:
                raise OrderException(self.replies["rate_limit_exceeded"])

            conversation.append(response.choices[0].message)

            # Save conversation and tokens
            self.chat_settings["conversation"] = [completion.model_dump() for completion in conversation]
            self.chat_settings["tokens"] = response.usage.total_tokens
            self.reply_to_sender(response.choices[0].message.content)
        else:
            # Clear conversation
            self.chat_settings.update(Conversation.default_chat_settings)
            self.reply_to_sender(self.replies["new_conversation"])

    def query_usage(self) -> None:
        if self.order_content:
            raise OrderInvalidError()

        self.update_reply_variables({
            "当前使用量": self.chat_settings["tokens"]
        })
        self.reply_to_sender(self.replies["query_usage"])

    def set_guidance(self) -> None:
        if not self.order_content:
            raise OrderInvalidError()

        conversation = self.load_conversation()
        conversation.append(ChatCompletion(
            role="system",
            content=self.order_content
        ))

        # Save conversation
        self.chat_settings["conversation"] = [completion.model_dump() for completion in conversation]
        self.reply_to_sender(self.replies["set_guidance"])

    # def load_guidance(self) -> None:
    #     guidance = None
    #
    #     for _guidance in self.chat_settings["saved_guidance"]:
    #         if _guidance["name"] == self.order_content:
    #             guidance = _guidance
    #             break
    #
    #     if not guidance:
    #         self.reply_to_sender(self.replies["guidance_not_found"])
    #         return
    #
    #     conversation = self.load_conversation()
    #     conversation.append(ChatCompletion(
    #         role="system",
    #         content=guidance["guidance"]
    #     ))
    #
    #     # Save conversation
    #     self.chat_settings["conversation"] = [completion.model_dump() for completion in conversation]
    #     self.update_reply_variables({
    #         "设定名称": self.order_content
    #     })
    #     self.reply_to_sender(self.replies["load_guidance"])

    def load_conversation(self) -> list[ChatCompletion]:
        conversation = []

        if self.chat_settings["conversation"]:
            try:
                # Load saved conversation
                conversation = TypeAdapter(list[ChatCompletion]).validate_python(self.chat_settings["conversation"])
            except ValidationError:
                # Clear conversation
                self.chat_settings.update(Conversation.default_chat_settings)
                raise OrderException(self.replies["conversation_invalid"])

        return conversation
