from plugin import OrderPlugin
from app.exceptions import OrderInvalidError, OrderError
from app.models import BaseModel
from app.models.report.segment import Image
from app.network import Client


class StableDiffusion(OrderPlugin):
    name = "dicerobot.stable_diffusion"
    display_name = "Stable Diffusion"
    description = "使用 Stability AI 的 Stable Diffusion 模型生成图片"
    version = "1.1.0"
    priority = 100
    orders = [
        "sd"
    ]
    default_plugin_settings = {
        "domain": "api.stability.ai",
        "api_key": "",
        "service": "ultra",
        "aspect_ratio": "1:1"
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
                "prompt": self.order_content,
                "aspect_ratio": self.plugin_settings["aspect_ratio"],
                "output_format": "png"
            })
        except ValueError:
            raise OrderInvalidError

        result = (await Client().post(
            "https://" + self.plugin_settings["domain"] + "/v2beta/stable-image/generate/" + self.plugin_settings["service"],
            headers={
                "Authorization": f"Bearer {api_key}"
            },
            files={key: (None, value) for key, value in request.model_dump(exclude_none=True).items()},
            timeout=60
        )).json()

        try:
            response = ImageGenerationResponse.model_validate(result)
        except (ValueError, ValueError):
            raise OrderError(self.replies["content_policy_violated"])

        await self.reply_to_sender([Image(data=Image.Data(file=f"base64://{response.image}"))])


class ImageGenerationRequest(BaseModel):
    prompt: str
    negative_prompt: str = None
    aspect_ratio: str = None
    seed: int = None
    output_format: str = None


class ImageGenerationResponse(BaseModel):
    image: str
    finish_reason: str
    seed: int
