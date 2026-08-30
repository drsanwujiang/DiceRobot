"""命令行入口。"""

from __future__ import annotations

import uvicorn

from dicerobot.config import get_settings

__all__ = ["main"]


def main() -> None:
    """启动 HTTP 服务。

    平台要求回调地址使用 HTTPS，且端口限于 80、443、8080、8443。此处默认仅监听本机，
    由反向代理负责 TLS 与端口映射。
    """

    settings = get_settings()

    uvicorn.run(
        "dicerobot.app:create_app",
        factory=True,
        host=settings.host,
        port=settings.port,
        # 日志已由 loguru 统一接管。
        log_config=None,
    )


if __name__ == "__main__":
    main()
