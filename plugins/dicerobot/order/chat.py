from pydantic import conlist, ValidationError

from app.exceptions import OrderInvalidError, OrderException
from app.internal import BaseModel
from app.internal.network import client
from plugins import OrderPlugin


class Chat(OrderPlugin):
    name = "dicerobot.chat"
    display_name = "聊天（GPT）"
    description = "使用 OpenAI 的 GPT 模型进行聊天对话"
    version = "1.0.0"

    default_settings = {
        "domain": "api.openai.com",
        "api_key": "",
        "model": "gpt-4"
    }
    default_replies = {
        "rate_limit_exceeded": "哎呀，思考不过来了呢……请重新再试一次吧~"
    }

    orders = [
        "chat", "聊天"
    ]
    priority = 100

    def __call__(self) -> None:
        try:
            request = ChatCompletionRequest.model_validate({
                "model": self.settings["model"],
                "messages": [{
                    "role": "user",
                    "content": self.order_content
                }],
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
            timeout=30
        ).json()

        try:
            response = ChatCompletionResponse.model_validate(result)
        except ValidationError:
            raise OrderException(self.replies["rate_limit_exceeded"])

        self.reply_to_sender(response.choices[0].message.content)


class ChatCompletion(BaseModel):
    role: str
    content: str


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
