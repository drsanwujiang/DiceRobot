"""create chats and members

Revision ID: c02e06c14054
Revises:
Create Date: 2026-08-29 23:26:16.456820
"""

from __future__ import annotations

from collections.abc import Sequence

import sqlalchemy as sa
from alembic import op

revision: str = "c02e06c14054"
down_revision: str | None = None
branch_labels: str | Sequence[str] | None = None
depends_on: str | Sequence[str] | None = None

_SCENE = sa.Enum("group", "c2c", name="scene")
_OPENID = sa.String(length=64)


def upgrade() -> None:
    op.create_table(
        "chats",
        sa.Column("scene", _SCENE, nullable=False),
        sa.Column("openid", _OPENID, nullable=False),
        sa.Column("enabled", sa.Boolean(), nullable=False),
        sa.Column("default_surface", sa.Integer(), nullable=False),
        sa.Column("rule", sa.String(length=32), nullable=False),
        sa.Column(
            "created_at",
            sa.DateTime(timezone=True),
            server_default=sa.text("(CURRENT_TIMESTAMP)"),
            nullable=False,
        ),
        sa.Column(
            "updated_at",
            sa.DateTime(timezone=True),
            server_default=sa.text("(CURRENT_TIMESTAMP)"),
            nullable=False,
        ),
        sa.PrimaryKeyConstraint("scene", "openid"),
    )
    op.create_table(
        "members",
        sa.Column("scene", _SCENE, nullable=False),
        sa.Column("chat_openid", _OPENID, nullable=False),
        sa.Column("openid", _OPENID, nullable=False),
        sa.Column("nickname", sa.String(length=32), nullable=True),
        sa.Column(
            "created_at",
            sa.DateTime(timezone=True),
            server_default=sa.text("(CURRENT_TIMESTAMP)"),
            nullable=False,
        ),
        sa.Column(
            "updated_at",
            sa.DateTime(timezone=True),
            server_default=sa.text("(CURRENT_TIMESTAMP)"),
            nullable=False,
        ),
        sa.ForeignKeyConstraint(
            ["scene", "chat_openid"],
            ["chats.scene", "chats.openid"],
            ondelete="CASCADE",
        ),
        sa.PrimaryKeyConstraint("scene", "chat_openid", "openid"),
    )


def downgrade() -> None:
    op.drop_table("members")
    op.drop_table("chats")
