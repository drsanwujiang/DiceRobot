from typing import Literal
import base64
import mimetypes

from pydantic import conlist

from plugin import OrderPlugin
from app.exceptions import OrderInvalidError, OrderError
from app.models import BaseModel
from app.models.report.segment import Text, Image
from app.network import Client
from app.network.napcat import get_image


class Chat(OrderPlugin):
    name = "dicerobot.chat"
    display_name = "聊天（GPT）"
    description = "使用 OpenAI 的 GPT 模型进行聊天对话"
    version = "1.2.1"

    default_plugin_settings = {
        "domain": "api.openai.com",
        "api_key": "",
        "model": "gpt-4o"
    }

    default_replies = {
        "unusable": "请先设置神秘代码~",
        "rate_limit_exceeded": "哎呀，思考不过来了呢……请重新再试一次吧~"
    }

    orders = [
        "chat", "聊天"
    ]
    priority = 100

    def __call__(self) -> None:
        self.check_order_content()
        self.check_repetition()

        if not (api_key := self.plugin_settings["api_key"]):
            raise OrderError(self.replies["unusable"])

        try:
            content = []

            for segment in self.message.message:
                if isinstance(segment, Text):
                    content.append(ChatCompletionTextContent.model_validate({
                        "type": "text",
                        "text": segment.data.text
                    }))
                elif isinstance(segment, Image):
                    file = get_image(segment.data.file).data.file
                    mime_type, _ = mimetypes.guess_type(file)

                    with open(file, "rb") as f:
                        image_content = base64.b64encode(f.read()).decode()

                    content.append(ChatCompletionImageUrlContent.model_validate({
                        "type": "image_url",
                        "image_url": {
                            "url": f"data:{mime_type};base64,{image_content}"
                        }
                    }))

            request = ChatCompletionRequest.model_validate({
                "model": self.plugin_settings["model"],
                "messages": [{
                    "role": "user",
                    "content": content
                }]
            })
        except ValueError:
            raise OrderInvalidError

        result = Client().post(
            "https://" + self.plugin_settings["domain"] + "/v1/chat/completions",
            headers={
                "Authorization": f"Bearer {api_key}"
            },
            json=request.model_dump(exclude_none=True),
            timeout=30
        ).json()

        try:
            response = ChatCompletionResponse.model_validate(result)
        except ValueError:
            raise OrderError(self.replies["rate_limit_exceeded"])

        self.reply_to_sender(response.choices[0].message.content)


class ChatCompletionContent(BaseModel):
    type: Literal["text", "image_url"]


class ChatCompletionTextContent(ChatCompletionContent):
    type: Literal["text"] = "text"
    text: str


class ChatCompletionImageUrlContent(ChatCompletionContent):
    class ImageUrl(BaseModel):
        url: str

    type: Literal["image_url"] = "image_url"
    image_url: ImageUrl


class ChatCompletion(BaseModel):
    role: str
    content: str | list[ChatCompletionContent]


class ChatCompletionRequest(BaseModel):
    model: str
    messages: list[ChatCompletion]
    frequency_penalty: float = None
    logit_bias: dict[str, float] = None
    max_tokens: int = None
    n: int = None
    presence_penalty: float = None
    response_format: dict[str, str] = None
    seed: int = None
    stop: str | list[str] = None
    stream: bool = None
    temperature: float = None
    top_p: float = None
    tools: list[dict[str, dict[str, str | dict]]] = None
    tool_choice: str | dict[str, dict[str, str]] = None
    user: str = None


class ChatCompletionResponse(BaseModel):
    class ChatCompletionChoice(BaseModel):
        index: int
        message: ChatCompletion
        finish_reason: str

    class ChatCompletionUsage(BaseModel):
        prompt_tokens: int
        completion_tokens: int
        total_tokens: int

    id: str
    object: str
    created: int
    model: str
    system_fingerprint: str = None
    choices: conlist(ChatCompletionChoice, min_length=1)
    usage: ChatCompletionUsage
