from typing import Literal
import base64
import mimetypes

from pydantic import conlist
import aiofiles

from app.exceptions import OrderInvalidError, OrderError
from app.models import BaseModel
from app.models.report.segment import Segment, Text, Image, Reply
from app.network import HttpClient
from plugin import OrderPlugin


class Chat(OrderPlugin):
    name = "dicerobot.chat"
    display_name = "AI 聊天"
    description = "使用大语言模型进行聊天对话"
    version = "2.2.0"
    priority = 100
    orders = [
        "chat", "聊天",
        "think", "思考"
    ]
    default_plugin_settings = {
        "base_url": "https://dashscope.aliyuncs.com/compatible-mode/v1",
        "api_key": "",
        "model": "qwen3-235b-a22b"
    }
    default_replies = {
        "unusable": "请先设置神秘代码~",
        "response_invalid": "哎呀，AI有点忙不过来了呢……请重新再试一次吧~"
    }

    async def __call__(self) -> None:
        self.check_order_content()
        self.check_repetition()

        if not (api_key := self.plugin_settings["api_key"]):
            raise OrderError(self.replies["unusable"])

        model = self.plugin_settings["model"]
        enable_thinking = None

        # Only for Qwen
        if model.startswith("qwen"):
            if self.order in ["chat", "聊天"]:
                enable_thinking = False
            elif self.order in ["think", "思考"]:
                enable_thinking = True

        try:
            content = []

            for segment in self.message.message:
                if isinstance(segment, Text):
                    content.append(ChatCompletionTextContent.model_validate({
                        "type": "text",
                        "text": segment.data.text
                    }))
                elif isinstance(segment, Image):
                    file = (await self.context.network_manager.napcat.get_image(segment.data.file)).data.file
                    mime_type, _ = mimetypes.guess_type(file)

                    async with aiofiles.open(file, "rb") as f:
                        image_content = base64.b64encode(await f.read()).decode()

                    content.append(ChatCompletionImageUrlContent.model_validate({
                        "type": "image_url",
                        "image_url": {
                            "url": f"data:{mime_type};base64,{image_content}"
                        }
                    }))

            request = ChatCompletionRequest.model_validate({
                "model": model,
                "messages": [{
                    "role": "user",
                    "content": content
                }],
                "stream": True,
                "enable_thinking": enable_thinking
            })
        except ValueError:
            raise OrderInvalidError

        completion_content = ""

        async with HttpClient() as client:
            async with client.stream(
                "POST",
                self.plugin_settings["base_url"].rstrip("/") + "/chat/completions",
                headers={
                    "Authorization": f"Bearer {api_key}"
                },
                json=request.model_dump(exclude_none=True)
            ) as response:
                async for chunk in response.aiter_bytes():
                    for line in chunk.decode().strip().split("\n\n"):
                        if not line.startswith("data:"):
                            continue

                        if (data := line[5:].strip()) == "[DONE]":
                            break

                        try:
                            completion_chunk = ChatCompletionChunk.model_validate_json(data)
                        except ValueError:
                            raise OrderError(self.replies["response_invalid"])

                        if content := completion_chunk.choices[0].delta.content:
                            completion_content += content

        reply: list[Segment] = [Text(data=Text.Data(text=completion_content))]

        if self.message.from_group:
            reply.insert(0, Reply(data=Reply.Data(id=self.message.message_id)))

        await self.reply_to_sender(reply)


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
    stream: bool = None
    enable_thinking: bool = None  # Only for Qwen


class ChatCompletionChunk(BaseModel):
    class ChatCompletionChoice(BaseModel):
        class ChatCompletionDelta(BaseModel):
            role: str = None
            content: str | None = None
            reasoning_content: str | None = None

        index: int
        delta: ChatCompletionDelta
        logprobs: dict | None
        finish_reason: str | None

    class ChatCompletionUsage(BaseModel):
        prompt_tokens: int
        completion_tokens: int
        total_tokens: int

    id: str
    object: str
    created: int
    model: str
    system_fingerprint: str | None = None
    choices: conlist(ChatCompletionChoice, min_length=1)
    usage: ChatCompletionUsage | None = None
