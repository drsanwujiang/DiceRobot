"""QQ 开放平台适配。"""

from __future__ import annotations

__all__ = ["API_BASE_URL"]

API_BASE_URL = "https://api.bot.qq.com"
"""平台接口域名。

V2 已将调用域名统一至此：换取 access token 与调用 OpenAPI 共用，且不再区分环境。
"""
