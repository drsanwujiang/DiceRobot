from apscheduler.triggers.cron import CronTrigger
import arrow

from app.context import AppContext
from app.exceptions import OrderInvalidError, OrderError
from app.enum import ChatType
from app.models.report.segment import Image
from app.network import HttpClient
from plugin import OrderPlugin


class DailySixtySeconds(OrderPlugin):
    name = "dicerobot.daily_60s"
    display_name = "每天60秒读懂世界"
    description = "每天60秒读懂世界，15条简报+1条微语，让你瞬间了解世界正在发生的大事"
    version = "1.3.0"
    priority = 100
    orders = [
        "60s", "60秒"
    ]
    default_plugin_settings = {
        "api": "https://api.2xb.cn/zaob",
        "subscribers": []
    }
    default_replies = {
        "api_error": "哎呀，今天的简报还没有寄过来呢……",
        "subscribe": "订阅成功~每天准时带你了解世界正在发生的大事",
        "unsubscribe": "取消订阅成功~",
        "unsubscribable": "只能在群聊中订阅哦~"
    }

    JOB_ID = f"{name}.send"

    @classmethod
    async def initialize(cls, context: AppContext) -> None:
        await context.task_manager.scheduler.add_schedule(cls.send_daily_60s, CronTrigger(hour=10), id=cls.JOB_ID)

    @classmethod
    async def send_daily_60s(cls, context: AppContext) -> None:
        settings = context.plugin_settings.get(plugin=cls.name)

        async with HttpClient() as client:
            result = (await client.get(settings["api"])).json()

        if result["datatime"] == arrow.now().format("YYYY-MM-DD"):
            message = [Image(data=Image.Data(file=result["imageUrl"]))]
        else:
            message = context.replies.get_reply(group=cls.name, key="api_error")

        for chat_id in settings["subscribers"]:
            await context.network_manager.napcat.send_group_message(chat_id, message)

    async def __call__(self) -> None:
        self.check_order_content()
        self.check_repetition()

        if self.chat_type != ChatType.GROUP:
            raise OrderError(self.replies["unsubscribable"])

        if self.chat_id not in self.plugin_settings["subscribers"]:
            self.plugin_settings["subscribers"].append(self.chat_id)
            self.save_plugin_settings()
            await self.reply_to_sender(self.replies["subscribe"])
        else:
            self.plugin_settings["subscribers"].remove(self.chat_id)
            self.save_plugin_settings()
            await self.reply_to_sender(self.replies["unsubscribe"])

    def check_order_content(self) -> None:
        if self.order_content:
            raise OrderInvalidError
