"""跨层共享的枚举。

放在包根而非某一层内：:class:`Scene` 既是运行时的路由依据，也是持久化的主键组成，
而依赖方向为 ``bot -> storage``，不允许 ``storage`` 反向引用运行时层。
"""

from __future__ import annotations

from enum import StrEnum

__all__ = ["MemberRole", "Scene"]


class Scene(StrEnum):
    """消息发生的场景，决定回复配额、发送接口与会话标识的含义。"""

    GROUP = "group"
    C2C = "c2c"


class MemberRole(StrEnum):
    """发送者在群内的身份。单聊没有这一概念。"""

    OWNER = "owner"
    ADMIN = "admin"
    MEMBER = "member"
