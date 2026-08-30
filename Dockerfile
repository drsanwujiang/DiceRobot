# syntax=docker/dockerfile:1

FROM python:3.13-slim AS builder

ENV POETRY_VIRTUALENVS_IN_PROJECT=1 \
    POETRY_NO_INTERACTION=1 \
    PIP_DISABLE_PIP_VERSION_CHECK=1

RUN pip install --no-cache-dir "poetry>=2.0,<3.0"

WORKDIR /app

# 依赖先于源码复制，源码变动时不必重装依赖。
COPY pyproject.toml poetry.lock README.md ./
# 只装运行期依赖，不装本项目自身：运行时以 python -m dicerobot 从源码目录启动，
# 无需依赖 editable 安装留下的路径。
RUN poetry install --only main --no-root


FROM python:3.13-slim

ENV PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1 \
    PATH="/app/.venv/bin:$PATH" \
    HOST=0.0.0.0 \
    PORT=8080

WORKDIR /app

COPY --from=builder /app/.venv ./.venv
# alembic.ini 与 migrations 必须随镜像分发：迁移在应用启动时执行，
# 且配置文件按工作目录查找。
COPY alembic.ini ./
COPY migrations/ ./migrations/
COPY dicerobot/ ./dicerobot/

RUN useradd --create-home --uid 10001 dicerobot \
    && mkdir -p data logs \
    && chown -R dicerobot:dicerobot /app

USER dicerobot

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD ["python", "-c", "import urllib.request; urllib.request.urlopen('http://127.0.0.1:8080/health').read()"]

CMD ["python", "-m", "dicerobot"]
