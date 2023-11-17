from io import BytesIO
import base64

from pydantic import ValidationError, model_validator
from PIL import Image as PILImage

from app.exceptions import OrderInvalidError, OrderException
from app.internal import BaseModel
from app.internal.network import client
from app.internal.message import Image
from plugins import OrderPlugin


class Paint(OrderPlugin):
    name = "dicerobot.paint"
    display_name = "画图（DALL·E）"
    description = "使用 OpenAI 的 DALL·E 模型生成图片"
    version = "1.0.0"

    default_settings = {
        "domain": "api.openai.com",
        "api_key": "",
        "model": "dall-e-3",
        "size": "1024x1024",
        "quality": "hd"
    }
    default_replies = {
        "content_policy_violated": "啊嘞，画出来的图图不见了……请重新再试一次吧~"
    }

    orders = [
        "paint", "画图", "画画", "生成图片", "生成图像"
    ]
    orders_priority = 100

    def __call__(self) -> None:
        try:
            request = ImageGenerationRequest.model_validate({
                "model": self.settings["model"],
                "prompt": self.order_content,
                "n": 1,
                "quality": self.settings["quality"],
                "response_format": "b64_json",
                "size": self.settings["size"],
                "user": f"{self.chat_type.value}-{self.chat_id}"
            })
        except ValidationError:
            raise OrderInvalidError()

        result = client.post(
            "https://" + self.settings["domain"] + "/v1/images/generations",
            headers={
                "Authorization": "Bearer " + self.settings["api_key"]
            },
            json=request.model_dump(exclude_none=True),
            timeout=30
        ).json()

        try:
            response = ImageGenerationResponse.model_validate(result)
        except (ValidationError, ValueError):
            raise OrderException(self.replies["content_policy_violated"])

        # Convert WebP to PNG
        image = PILImage.open(BytesIO(base64.b64decode(response.data[0].b64_json))).convert("RGB")
        buffer = BytesIO()
        image.save(buffer, format="png")
        image_base64 = base64.b64encode(buffer.getvalue()).decode()

        self.reply_to_sender([Image.model_validate({
            "base64": image_base64
        })])


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