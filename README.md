# DiceRobot

基于 QQ 开放平台的 TRPG 助手。

## 架构

```
dicerobot/
├── qq/        平台适配：签名校验、access token、OpenAPI 客户端、webhook 入口
├── bot/       运行时：事件归一化、回复配额、插件加载与调度
├── plugins/   内置插件：dice / check / nickname / system
├── trpg/      领域逻辑：掷骰引擎、检定规则（零 IO，不依赖任何外层包）
└── storage/   持久化：SQLAlchemy 模型、仓储与迁移
```

依赖方向单向：`plugins -> bot -> qq`、`bot -> trpg`、`bot -> storage`。

`qq` 为叶子包，不依赖任何内部模块。webhook 通过本地 `Protocol` 接收事件汇聚点，
由 `app.py` 负责装配，因此 `bot` 调用客户端发消息不会与 webhook 形成循环依赖。

`trpg` 同样不依赖任何其他层，该约束由 `dicerobot/trpg/ruff.toml` 在 CI 中强制。

## 开发

```bash
poetry install
poetry run pytest
poetry run ruff check .
poetry run mypy
```

数据库迁移在应用启动时自动执行，也可手动运行：

```bash
poetry run alembic upgrade head          # 升级到最新版本
poetry run alembic revision --autogenerate -m "说明"   # 依据模型变更生成迁移
```

迁移只需 `DATABASE_URL`（缺省时用默认值），不依赖机器人凭据。

## 指令

| 指令 | 说明 |
| --- | --- |
| `.r [表达式] [理由]` | 掷骰，如 `.r 3d6+2 侦查`、`.r 4d6k3`、`.r (2d6+3)*2` |
| `.ra <技能值> [理由]` | 技能检定，如 `.ra 60 侦查` |
| `.rule [规则]` | 查看或设置检定规则（`coc7`、`simple`） |
| `.nn [昵称]` | 设置昵称，`.nn 清除` 恢复默认 |
| `.set [面数]` | 查看或设置默认骰 |
| `.bot on` / `.bot off` | 启用或停用机器人 |
| `.help` | 列出可用指令 |

| `.plugin` / `.plugin off dice` | 查看或启停插件 |

指令支持 `#N` 后缀重复执行，如 `.r 1d100#3`。

## 插件

每条指令都归属于某个插件。插件在模块层声明，指令以装饰器挂载：

```python
plugin = Plugin(name="dice", display_name="掷骰", version="1.0.0", chat_settings=DiceChatSettings)


@plugin.command("r", "roll", "掷骰", description="掷骰，如 .r 3d6+2 侦查", max_times=30)
async def roll(context: CommandContext) -> None:
    settings = context.chat_settings(DiceChatSettings)
    ...
```

插件也可以响应非消息事件。这类事件自带 `event_id`，可作被动回复，因此不消耗主动
消息配额：

```python
@plugin.event(EventType.GROUP_ADD_ROBOT, EventType.FRIEND_ADD)
async def greet(context: EventContext) -> None:
    context.write("我是 DiceRobot，发送 .help 查看可用指令。")
```

同一事件可被多个插件响应，它们共用一个回复会话，输出合并为一条消息；单个插件出错
不影响其余插件，且不会向用户回复错误提示。

插件设置以 JSON 存储，读取时由声明的 pydantic 模型校验并补齐默认值，因此增删设置项
不需要迁移数据库。写回须显式调用 `save_settings` 或 `save_chat_settings`。

启停分三层依次判定：会话总开关（`.bot`）、插件全局开关、插件在本会话的开关
（`.plugin`）。标记 `requires_enabled=False` 的指令不受这三层影响，用于保证关闭之后
仍有恢复手段。

第三方插件是独立的分发包，通过 entry points 被发现：

```toml
[project.entry-points."dicerobot.plugins"]
mycards = "mycards:plugin"
```

配置从环境变量或 `.env` 读取，参见 `.env.example`。

Windows 下若测试输出中的中文显示为乱码，设置 `PYTHONUTF8=1` 即可。

## 部署

### 平台侧的硬性约束

- 回调地址必须是 **HTTPS**，端口只能是 **80、443、8080、8443** 之一
- 正式环境需要在开放平台配置 **IP 白名单**，家用宽带的动态 IP 会导致请求被拒
- 建议先在沙箱环境联调，再切正式环境

### 步骤

1. 在 [QQ 开放平台](https://bot.q.qq.com/) 创建机器人，取得 AppID 与 AppSecret。

2. 准备一台有公网 IP 的服务器和一个解析到它的域名。

3. 复制配置并填入凭据：

   ```bash
   cp .env.example .env
   ```

4. 把 `Caddyfile` 中的 `bot.example.com` 换成自己的域名。

5. 启动：

   ```bash
   docker compose up -d
   ```

   Caddy 会自动申请证书；数据库与日志分别落在 `./data` 与 `./logs`。

6. 在开放平台把回调地址填为 `https://<域名>/qq/webhook`。平台会立即下发一次校验
   请求（`op = 13`），应用签名回传后地址才会生效。

7. 在群里 `@机器人 .ping`，收到 `pong` 即联通。

### 运维

数据库迁移在每次启动时自动执行，且可重复运行，容器重建无需额外操作。

```bash
docker compose logs -f dicerobot   # 查看日志
docker compose pull && docker compose up -d   # 更新
```

镜像以非 root 用户运行，健康检查探测 `/health`。
