from app.exceptions import OrderInvalidError, OrderError
from app.models import BaseModel
from app.models.report.segment import Image
from app.network import HttpClient
from plugin import OrderPlugin


class DallE(OrderPlugin):
    name = "dicerobot.dall_e"
    display_name = "DALL·E"
    description = "使用 OpenAI 的 DALL·E 模型生成图片"
    version = "1.4.0"
    priority = 100
    orders = [
        "dalle", "dall_e", "dall-e"
    ]
    default_plugin_settings = {
        "domain": "api.openai.com",
        "api_key": "",
        "model": "dall-e-3",
        "size": "1024x1024",
        "quality": "hd"
    }
    default_replies = {
        "unusable": "请先设置神秘代码~",
        "content_policy_violated": "啊嘞，画出来的图图不见了……请重新再试一次吧~"
    }

    async def __call__(self) -> None:
        self.check_order_content()
        self.check_repetition()

        if not (api_key := self.plugin_settings["api_key"]):
            raise OrderError(self.replies["unusable"])

        try:
            request = ImageGenerationRequest.model_validate({
                "model": self.plugin_settings["model"],
                "prompt": self.order_content,
                "n": 1,
                "quality": self.plugin_settings["quality"],
                "size": self.plugin_settings["size"],
                "response_format": "b64_json"
            })
        except ValueError:
            raise OrderInvalidError

        async with HttpClient() as client:
            result = (await client.post(
                "https://" + self.plugin_settings["domain"] + "/v1/images/generations",
                headers={
                    "Authorization": f"Bearer {api_key}"
                },
                json=request.model_dump(exclude_none=True),
                timeout=60
            )).json()

        try:
            response = ImageGenerationResponse.model_validate(result)
        except (ValueError, ValueError):
            raise OrderError(self.replies["content_policy_violated"])

        await self.reply_to_sender([Image(data=Image.Data(file=f"base64://{response.data[0].b64_json}"))])


class ImageGenerationRequest(BaseModel):
    model: str
    prompt: str
    n: int = None
    quality: str = None
    response_format: str = None
    size: str = None
    style: str = None
    user: str = None


class ImageGenerationResponse(BaseModel):
    class Image(BaseModel):
        b64_json: str
        revised_prompt: str

    created: int
    data: list[Image]
