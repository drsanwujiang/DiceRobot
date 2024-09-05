import datetime

from plugin import OrderPlugin
from app.schedule import scheduler
from app.exceptions import OrderInvalidError, OrderError
from app.enum import ChatType
from app.models.report.segment import Image
from app.network import Client


class DailySixtySeconds(OrderPlugin):
    name = "dicerobot.daily_60s"
    display_name = "每天60秒读懂世界"
    description = "每天60秒读懂世界，15条简报+1条微语，让你瞬间了解世界正在发生的大事"
    version = "1.1.2"

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

    orders = [
        "60s", "60秒"
    ]
    priority = 100

    @classmethod
    def initialize(cls) -> None:
        scheduler.add_job(cls.send_daily_60s, id=f"{cls.name}.send", trigger="cron", hour=10)

    @classmethod
    def send_daily_60s(cls) -> None:
        result = Client().get(cls.get_plugin_setting(key="api")).json()

        if result["datatime"] == str(datetime.date.today()):
            message = [Image(data=Image.Data(file=result["imageUrl"]))]
        else:
            message = cls.get_reply(reply_key="api_error")

        for chat_id in cls.get_plugin_setting(key="subscribers"):
            cls.send_group_message(chat_id, message)

    def __call__(self) -> None:
        self.check_order_content()

        if self.chat_type != ChatType.GROUP:
            raise OrderError(self.replies["unsubscribable"])

        if self.chat_id not in self.plugin_settings["subscribers"]:
            self.plugin_settings["subscribers"].append(self.chat_id)
            self.save_plugin_settings()
            self.reply_to_sender(self.replies["subscribe"])
        else:
            self.plugin_settings["subscribers"].remove(self.chat_id)
            self.save_plugin_settings()
            self.reply_to_sender(self.replies["unsubscribe"])

    def check_order_content(self) -> None:
        if self.order_content:
            raise OrderInvalidError
