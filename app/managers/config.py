from typing import TYPE_CHECKING
import json

from loguru import logger
from sqlalchemy import select
from sqlalchemy.dialects.sqlite import insert
from sqlalchemy.ext.asyncio import AsyncSession

from ..database import Settings, PluginSettings, Replies, ChatSettings
from . import Manager

if TYPE_CHECKING:
    from ..context import AppContext

__all__ = [
    "ConfigManager"
]


class ConfigManager(Manager):
    def __init__(self, context: "AppContext") -> None:
        super().__init__(context)

        self.dirty = False

    async def initialize(self) -> None:
        await self.load_config()
        logger.debug("Config manager initialized")

    async def cleanup(self) -> None:
        logger.debug("Clean config manager")
        await self.save_config()

    async def load_config(self) -> None:
        async with self.context.database_manager.get_session() as session:
            await self._load_settings(session)
            await self._load_plugin_settings(session)
            await self._load_chat_settings(session)
            await self._load_replies(session)

        logger.info("Configuration loaded")

    async def _load_settings(self, session: AsyncSession) -> None:
        try:
            result = await session.execute(select(Settings))
            records = result.scalars().all()
            settings_dict = {}

            for item in records:
                try:
                    settings_dict[item.group] = json.loads(item.json)
                except json.JSONDecodeError:
                    logger.exception(f"Failed to parse settings, group: {item.group}")
                    continue

            self.context.settings.update(settings_dict)
        except Exception:
            logger.exception("Failed to load settings")
            raise

    async def _load_plugin_settings(self, session: AsyncSession) -> None:
        try:
            result = await session.execute(select(PluginSettings))
            records = result.scalars().all()

            for item in records:
                try:
                    settings = json.loads(item.json)
                    self.context.plugin_settings.set(
                        plugin=item.plugin,
                        settings=settings
                    )
                except json.JSONDecodeError:
                    logger.exception(f"Failed to parse plugin settings of \"{item.plugin}\"")
                    continue
        except Exception:
            logger.exception("Failed to load plugin settings")
            raise

    async def _load_chat_settings(self, session: AsyncSession) -> None:
        try:
            result = await session.execute(select(ChatSettings))
            records = result.scalars().all()

            for item in records:
                try:
                    settings = json.loads(item.json)
                    self.context.chat_settings.set(
                        chat_type=item.chat_type,
                        chat_id=item.chat_id,
                        settings_group=item.group,
                        settings=settings
                    )
                except json.JSONDecodeError:
                    logger.exception(f"Failed to parse chat settings of \"{item.chat_type}/{item.chat_id}/{item.group}\"")
                    continue
        except Exception:
            logger.exception("Failed to load chat settings")
            raise

    async def _load_replies(self, session: AsyncSession) -> None:
        try:
            result = await session.execute(select(Replies))
            records = result.scalars().all()
            replies_dict: dict[str, dict[str, str]] = {}

            for item in records:
                replies_dict.setdefault(item.group, {})[item.key] = item.value

            for group, group_replies in replies_dict.items():
                self.context.replies.set_replies(group=group, replies=group_replies)
        except Exception:
            logger.exception("Failed to load replies")
            raise

    async def save_config(self) -> None:
        if not self.dirty:
            logger.debug("No configuration changes to save")
            return

        logger.info("Save configuration")

        async with self.context.database_manager.get_session() as session:
            await self._save_settings(session)
            await self._save_plugin_settings(session)
            await self._save_chat_settings(session)
            await self._save_replies(session)

        self.dirty = False

    async def _save_settings(self, session: AsyncSession) -> None:
        data = self.context.settings.model_dump(safe_dump=False)

        for key, value in data.items():
            stmt = insert(Settings).values(
                group=key,
                json=json.dumps(value)
            )
            stmt = stmt.on_conflict_do_update(
                index_elements=["group"],
                set_={"json": stmt.excluded.json}
            )
            await session.execute(stmt)

    async def _save_plugin_settings(self, session: AsyncSession) -> None:
        data = self.context.plugin_settings.model_dump()

        for key, value in data.items():
            stmt = insert(PluginSettings).values(
                plugin=key,
                json=json.dumps(value)
            )
            stmt = stmt.on_conflict_do_update(
                index_elements=["plugin"],
                set_={"json": stmt.excluded.json}
            )
            await session.execute(stmt)

    async def _save_chat_settings(self, session: AsyncSession) -> None:
        data = self.context.chat_settings.model_dump()

        for type_, settings in data.items():
            for id_, chat_settings in settings.items():
                for key, value in chat_settings.items():
                    stmt = insert(ChatSettings).values(
                        chat_type=type_.value,
                        chat_id=id_,
                        group=key,
                        json=json.dumps(value)
                    )
                    stmt = stmt.on_conflict_do_update(
                        index_elements=["chat_type", "chat_id", "group"],
                        set_={"json": stmt.excluded.json}
                    )
                    await session.execute(stmt)

    async def _save_replies(self, session: AsyncSession) -> None:
        data = self.context.replies.model_dump()

        for group, replies in data.items():
            for key, value in replies.items():
                stmt = insert(Replies).values(
                    group=group,
                    key=key,
                    value=value
                )
                stmt = stmt.on_conflict_do_update(
                    index_elements=["group", "key"],
                    set_={"value": stmt.excluded.value}
                )
                await session.execute(stmt)
