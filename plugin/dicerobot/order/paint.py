from plugin import OrderPlugin
from app.exceptions import OrderInvalidError, OrderError
from app.models import BaseModel
from app.models.report.segment import Image
from app.network import Client


class Paint(OrderPlugin):
    name = "dicerobot.paint"
    display_name = "画图（DALL·E）"
    description = "使用 OpenAI 的 DALL·E 模型生成图片"
    version = "1.2.1"

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

    orders = [
        "paint", "画图", "画画", "生成图片", "生成图像"
    ]
    priority = 100

    def __call__(self) -> None:
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

        result = Client().post(
            "https://" + self.plugin_settings["domain"] + "/v1/images/generations",
            headers={
                "Authorization": f"Bearer {api_key}"
            },
            json=request.model_dump(exclude_none=True),
            timeout=60
        ).json()

        try:
            response = ImageGenerationResponse.model_validate(result)
        except (ValueError, ValueError):
            raise OrderError(self.replies["content_policy_violated"])

        self.reply_to_sender([Image(data=Image.Data(file=f"base64://{response.data[0].b64_json}"))])


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
