"""持久化层。

SQLite 配合 SQLAlchemy 异步引擎，表结构变更由 alembic 管理。
"""

from __future__ import annotations

from dicerobot.storage.database import Database
from dicerobot.storage.migrations import upgrade_to_head
from dicerobot.storage.models import Base, Chat, ChatPluginState, Member, PluginState
from dicerobot.storage.repositories import Store

__all__ = [
    "Base",
    "Chat",
    "ChatPluginState",
    "Database",
    "Member",
    "PluginState",
    "Store",
    "upgrade_to_head",
]
