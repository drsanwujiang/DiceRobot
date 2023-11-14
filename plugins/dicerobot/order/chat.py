from pydantic import conlist, ValidationError

from app.exceptions import OrderInvalidException, OrderException
from app.internal import BaseModel
from app.internal.network import client
from plugins import OrderPlugin


class Chat(OrderPlugin):
    name = "dicerobot.chat"
    description = ""
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
    default_priority = 100

    def __call__(self) -> None:
        try:
            request = ChatCompletionRequest.model_validate({
                "model": self.plugin_settings["model"],
                "messages": [{
                    "role": "user",
                    "content": self.order_content
                }],
                "user": f"{self.chat_type.value}-{self.chat_id}"
            })
        except ValidationError:
            raise OrderInvalidException()

        result = client.post(
            "https://" + self.plugin_settings["domain"] + "/v1/chat/completions",
            headers={
                "Authorization": "Bearer " + self.plugin_settings["api_key"]
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
