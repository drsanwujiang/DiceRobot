from unittest.mock import AsyncMock
import json

import pytest
from sqlalchemy import select, update
from sqlalchemy.dialects.sqlite import insert
from sqlalchemy.ext.asyncio import AsyncSession

from app.database import Settings
from app.context import AppContext
from app.managers.config import ConfigManager

pytestmark = pytest.mark.asyncio


async def test_load_settings(db_session: AsyncSession, context: AppContext) -> None:
    secret = "just_a_test_secret"
    settings = json.dumps({
        "jwt": {
            "secret": secret,
            "algorithm": "HS256"
        }
    })

    await db_session.execute(insert(Settings).values(group="security", json=settings))
    await db_session.commit()

    await context.config_manager.load_config()
    assert context.settings.security.jwt.secret == secret


async def test_load_settings_invalid(db_session: AsyncSession, context: AppContext) -> None:
    settings = "{not-json"

    await db_session.execute(update(Settings).values(group="security", json=settings))
    await db_session.commit()

    await context.config_manager.load_config()


async def test_save_settings(db_session: AsyncSession, context: AppContext) -> None:
    new_secret = "a_new_secret_to_save"
    context.settings.update_security({
        "jwt": {
            "secret": new_secret
        }
    })

    context.config_manager.dirty = True
    await context.config_manager.save_config()

    result = (await db_session.execute(select(Settings).where(Settings.group == "security"))).scalar_one_or_none()
    assert result is not None

    settings = json.loads(result.json)
    assert settings["jwt"]["secret"] == new_secret
    assert context.config_manager.dirty is False


async def test_save_config_no_dirty(context: AppContext, monkeypatch: pytest.MonkeyPatch) -> None:
    mock = AsyncMock()
    monkeypatch.setattr(ConfigManager, "_save_settings", mock)
    monkeypatch.setattr(ConfigManager, "_save_plugin_settings", mock)
    monkeypatch.setattr(ConfigManager, "_save_chat_settings", mock)
    monkeypatch.setattr(ConfigManager, "_save_replies", mock)

    context.config_manager.dirty = False
    await context.config_manager.save_config()
    mock.assert_not_called()
    mock.assert_not_awaited()
