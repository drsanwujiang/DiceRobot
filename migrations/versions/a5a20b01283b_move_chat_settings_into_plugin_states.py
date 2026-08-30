"""move chat settings into plugin states

把 chats 表上的 default_surface 与 rule 两列搬进插件设置。这两项分别属于掷骰与
技能检定插件，留在核心表上意味着每加一个可配置的插件就要改表结构，第三方插件更是
无从下手。

Revision ID: a5a20b01283b
Revises: c02e06c14054
Create Date: 2026-08-30
"""

from __future__ import annotations

import json
from collections.abc import Sequence

import sqlalchemy as sa
from alembic import op

revision: str = "a5a20b01283b"
down_revision: str | None = "c02e06c14054"
branch_labels: str | Sequence[str] | None = None
depends_on: str | Sequence[str] | None = None

_SCENE = sa.Enum("group", "c2c", name="scene")
_OPENID = sa.String(length=64)
_PLUGIN = sa.String(length=64)

_DEFAULT_SURFACE = 100
_DEFAULT_RULE = "coc7"


def _timestamp_columns() -> list[sa.Column[sa.DateTime]]:
    return [
        sa.Column(
            name,
            sa.DateTime(timezone=True),
            server_default=sa.text("(CURRENT_TIMESTAMP)"),
            nullable=False,
        )
        for name in ("created_at", "updated_at")
    ]


def upgrade() -> None:
    op.create_table(
        "plugin_states",
        sa.Column("plugin", _PLUGIN, nullable=False),
        sa.Column("enabled", sa.Boolean(), nullable=False),
        sa.Column("settings", sa.JSON(), nullable=False),
        *_timestamp_columns(),
        sa.PrimaryKeyConstraint("plugin"),
    )
    op.create_table(
        "chat_plugin_states",
        sa.Column("scene", _SCENE, nullable=False),
        sa.Column("chat_openid", _OPENID, nullable=False),
        sa.Column("plugin", _PLUGIN, nullable=False),
        sa.Column("enabled", sa.Boolean(), nullable=False),
        sa.Column("settings", sa.JSON(), nullable=False),
        *_timestamp_columns(),
        sa.ForeignKeyConstraint(
            ["scene", "chat_openid"],
            ["chats.scene", "chats.openid"],
            ondelete="CASCADE",
        ),
        sa.PrimaryKeyConstraint("scene", "chat_openid", "plugin"),
    )

    _migrate_settings(
        select="SELECT scene, openid, default_surface, rule FROM chats",
        build={
            "dice": lambda row: {"default_surface": row.default_surface},
            "check": lambda row: {"rule": row.rule},
        },
    )

    # SQLite 不支持直接删列，需以重建表的方式进行。
    with op.batch_alter_table("chats") as batch:
        batch.drop_column("default_surface")
        batch.drop_column("rule")


def downgrade() -> None:
    with op.batch_alter_table("chats") as batch:
        batch.add_column(sa.Column("default_surface", sa.Integer(), nullable=False, server_default="100"))
        batch.add_column(sa.Column("rule", sa.String(length=32), nullable=False, server_default="coc7"))

    _restore_settings()

    op.drop_table("chat_plugin_states")
    op.drop_table("plugin_states")


def _migrate_settings(*, select: str, build: dict[str, object]) -> None:
    """把每个会话的两列取值写入对应插件的设置。

    以 Python 逐行搬运而非单条 INSERT ... SELECT：后者要依赖数据库各自的 JSON 函数，
    而这一步在任何后端上都应当行为一致。
    """

    connection = op.get_bind()
    rows = connection.execute(sa.text(select)).fetchall()
    insert = sa.text(
        "INSERT INTO chat_plugin_states (scene, chat_openid, plugin, enabled, settings) "
        "VALUES (:scene, :chat_openid, :plugin, 1, :settings)"
    )

    for row in rows:
        for plugin, make_settings in build.items():
            connection.execute(
                insert,
                {
                    "scene": row.scene,
                    "chat_openid": row.openid,
                    "plugin": plugin,
                    "settings": json.dumps(make_settings(row)),  # type: ignore[operator]
                },
            )


def _restore_settings() -> None:
    """把插件设置写回 chats 的两列。缺失或非法的取值退回默认值。"""

    connection = op.get_bind()
    rows = connection.execute(
        sa.text("SELECT scene, chat_openid, plugin, settings FROM chat_plugin_states WHERE plugin IN ('dice', 'check')")
    ).fetchall()
    update = sa.text(
        "UPDATE chats SET default_surface = :surface, rule = :rule WHERE scene = :scene AND openid = :openid"
    )
    restored: dict[tuple[str, str], dict[str, object]] = {}

    for row in rows:
        try:
            settings = json.loads(row.settings) if isinstance(row.settings, str) else row.settings
        except ValueError:
            settings = {}

        entry = restored.setdefault((row.scene, row.chat_openid), {})

        if row.plugin == "dice":
            entry["surface"] = settings.get("default_surface", _DEFAULT_SURFACE)
        else:
            entry["rule"] = settings.get("rule", _DEFAULT_RULE)

    for (scene, openid), entry in restored.items():
        connection.execute(
            update,
            {
                "scene": scene,
                "openid": openid,
                "surface": entry.get("surface", _DEFAULT_SURFACE),
                "rule": entry.get("rule", _DEFAULT_RULE),
            },
        )
