# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

DiceRobot 是基于 QQ 开放平台的 TRPG 掷骰机器人，Python 3.13 + FastAPI + SQLAlchemy(async) + SQLite，
以 Poetry 管理依赖。仓库内的注释、文档与提交信息一律使用中文，新增代码请保持一致：用词简洁，
使用通行的技术术语，不用口语表达与生造词。

## 常用命令

```bash
poetry install
poetry run pytest                                   # 全量测试
poetry run pytest tests/trpg/test_dice.py           # 单个文件
poetry run pytest tests/trpg/test_dice.py::TestBasicRolls::test_bare_d_uses_the_default_surface
poetry run pytest -k dice                           # 按名称筛选
poetry run ruff check .
poetry run ruff format --check .                    # CI 会校验格式，提交前请先格式化
poetry run mypy                                     # strict 模式，只检查 dicerobot 包
poetry run python -m dicerobot                      # 本地启动，需先配置 .env
```

迁移：

```bash
poetry run alembic upgrade head
poetry run alembic revision --autogenerate -m "说明"
poetry run alembic check          # CI 会执行：模型变更缺少对应迁移时在此暴露
```

Windows 下若测试输出中文乱码，设置 `PYTHONUTF8=1`。

分支：开发在 `dev`，`main` 只接受 PR 合入。

## 分层与依赖方向

```
dicerobot/
├── qq/        平台适配：Ed25519 验签、access token、OpenAPI 客户端、webhook 路由
├── bot/       运行时：事件归一化、队列调度、回复配额、插件加载与指令解析
├── plugins/   内置插件：dice / check / nickname / system
├── trpg/      领域逻辑：掷骰引擎、检定规则（零 IO）
└── storage/   持久化：SQLAlchemy 模型、Store 仓储、alembic 迁移
```

依赖单向：`plugins -> bot -> qq`、`bot -> trpg`、`bot -> storage`。

两条约束由**目录内的 `ruff.toml`** 在 CI 中强制，改动时不要绕过：

- `dicerobot/trpg/ruff.toml`：`trpg` 不得 import fastapi / httpx / sqlalchemy / loguru，也不得
  import 本项目任何外层包。掷骰引擎必须能完全离线开发与测试。
- `dicerobot/plugins/ruff.toml`：插件不得 import `dicerobot.qq.client`，输出一律经 `context.write`，
  否则回复配额无法统一计量。

`qq` 是叶子包：webhook 以本地 `Protocol`（`EventSink`）声明事件接收方，由 `app.py` 注入 `Pipeline`，
因此 `bot` 调用客户端发消息不会与 webhook 形成循环依赖。所有组件在 `create_app` 中装配，生命周期
（HTTP 连接池、token 后台刷新、pipeline worker、数据库引擎）统一由 lifespan 管理。

## 一条消息的完整路径

1. `qq/webhook.py` 收到 POST：先用**原始字节**验签（重新序列化会导致校验失败）；`op=13` 签名后回传，
   完成回调地址校验；`op=0` 交给 sink 后立即返回——平台超时未收到响应会重推同一事件。
2. `bot/pipeline.py::submit` 去重（`bot/dedup.py`，TTL 10 分钟）后入队；队列满时**丢弃并告警**而非
   反压。webhook 处理函数不得阻塞。
3. worker 取出事件 → `bot/message.py` 归一化为 `IncomingMessage` / `IncomingEvent`（剥离 `<@openid>`
   标记，统一群聊与单聊的字段结构，`ReplyTarget` 统一 `msg_id` 与 `event_id` 两种回复凭据）。
4. `bot/registry.py::resolve` 解析指令：要求 `.` 或 `。` 前缀，**最长别名优先**（故 `.ra` 命中检定
   而非掷骰），行尾 `#N` 为重复次数。未命中即丢弃——群可能开启全量消息推送，快速路径开销要低。
5. 整条指令共用一个数据库会话，构造 `CommandContext` 执行 handler，退出时统一提交。
6. `bot/outbound.py`：`ReplyBuffer` 把执行期间的多段 `write` 合并成一条消息，`ReplySession` 计量
   被动回复配额（群聊 5 分钟 5 条，单聊 60 分钟 4 条）。`msg_seq` 在发出前递增，失败也消耗配额。
   **回复在会话提交之后发出**：一次平台调用约 700 ms，横跨事务会让其他 worker 的提交等在写锁上。
   指令自行调用 `context.flush()` 发进度提示是例外，它必然在会话内。私聊输出排在回复之前发出，
   失败时在回复里追加提示。

整条链路的日志均携带事件 ID：webhook、`Pipeline.submit` 与 worker 各用
`logger.contextualize(event_id=...)` 绑定一次，处理期间的插件日志、平台调用日志与转发自标准库的
日志都会携带该字段；不属于任何事件的日志（启动、token 刷新）不含该列，日志消息中不再重复事件 ID。

各阶段耗时同样可由日志还原：webhook 与 worker 各记录一对开始、结束日志（DEBUG），结束时记录本阶段
耗时，worker 另记录排队耗时——耗时集中在排队说明 worker 消费速度不足，集中在处理则是单条指令慢。

非消息事件由 `_process_event` 派发：多个插件共享同一个 buffer 与 session，输出合并为一条回复；单个插件
失败不影响其余插件，且**不向用户回复错误提示**（入群等场景下会造成连续无效回复）。

## 插件模型

插件对象在模块层声明，handler 以装饰器挂载且装饰器原样返回函数，因此测试可以脱离运行时直接调用
handler。内置插件在 `bot/loader.py` 的 `_BUILTIN_MODULES` 清单中声明，第三方插件经 entry points
（`dicerobot.plugins` 组）发现；加载失败一律抛出而非静默跳过。`system` 插件需要遍历注册表，故由
loader 在其余插件之后用 `build_plugin(registry)` 构造。

- **三层开关**：会话总开关（`.bot`）→ 插件全局开关 → 插件在本会话的开关（`.plugin`）。开关类指令
  必须声明 `requires_enabled=False`，否则关闭后无法恢复；群聊中的启停仅限群主与管理员，依据
  `message.role`，未知取值一律拒绝。
- **两种输出**：`context.write` 进入本会话的被动回复；`context.write_private` 作为主动消息私聊发给
  发送者本人（暗骰即用此），只能发给发送者，不会成为群发手段。
- **设置以 JSON 存储**：`PluginState.settings` / `ChatPluginState.settings` 为 JSON 列，读取时由插件
  声明的 pydantic 模型校验并补齐默认值，因此增删设置项**不需要迁移数据库**。写回必须显式调用
  `save_settings` / `save_chat_settings`。
- **错误语义**：`CommandError.message` 会原样回复给玩家，措辞需面向用户；其余异常回复通用提示并记
  日志。`DEBUG=true` 时异常向上抛出而非被捕获。

## 数据模型要点

平台仅提供不透明的 openid：群消息中是 `member_openid`，单聊中是 `user_openid`。**实测同一用户在两
者中取值相同**，暗骰据此把群内掷出的结果私聊发给本人；但文档并未承诺这一点，故 `Chat` / `Member`
/ `ChatPluginState` 仍以 `(scene, openid…)` 为复合主键。会话与成员在首次出现时
由 `Store` 惰性创建（平台不提供成员列表），插入放在 SAVEPOINT 中：多个 worker 会同时遇到同一个新
会话，抢输的一方撞上主键冲突后改取对方创建的记录，本会话的其他改动不受影响。SQLite 不支持多数
ALTER TABLE，迁移使用 `render_as_batch=True`。连接建立时开启 WAL 与 busy_timeout
（`storage/database.py`）：默认的回滚日志下，一个 worker 的读事务会挡住其他 worker 的提交。

迁移在应用启动时自动执行（`storage/migrations.py`），alembic 入口是同步的且内部自行 `asyncio.run`，
故必须 `asyncio.to_thread` 调用；配置按**当前工作目录**查找 `alembic.ini`，进程必须从项目根启动。
`migrations/env.py` 只依赖 `DATABASE_URL`，不构造完整 `Settings`——迁移不应因缺少机器人凭据而失败。

## 平台约束（改动相关代码前务必知悉）

- 回复必须携带来源的 `msg_id` 或 `event_id`；两者都不传即成为主动消息。请求体中不能出现 `null` 的
  来源字段。
- 主动消息按用户计频（单聊 1000 条/用户/日），且用户可在客户端关闭，投递失败属于正常情形。暗骰用它
  把结果私聊给发起者，见 `bot/outbound.py::DirectSession`。
- access token 有效期 7200 秒，平台**仅在过期前 60 秒内**签发新的，提前刷新得到的仍是同一个。启动时
  预取 + 后台轮询刷新，401 时 `invalidate()` 后重试一次。
- Ed25519 密钥由 AppSecret 重复拼接至 32 字节派生；验签内容是 `timestamp + body`，回调校验签名内容
  是 `event_ts + plain_token`，顺序相反。
- 回调地址必须 HTTPS，端口限 80/443/8080/8443，且需在开放平台配置 IP 白名单。

## 配置

全部来自环境变量或 `.env`，不入库；嵌套字段以双下划线分隔（`QQ__APP_ID`、`LOG__LEVEL`）。
`BOT__WORKERS` 是并发槽位数而非 CPU 并行度：一条指令约 700 ms 全耗在等平台响应，所需槽位约等于
指令到达率乘以单条耗时，故默认取 32；出站连接的保活上限在 `app.py` 中与之对齐。各分节继承
`_Section`（`extra="forbid"`），字段名拼错会直接报错而非静默回退默认值。`get_settings()` 带 lru_cache，
测试需要时调用 `get_settings.cache_clear()`。

## 测试约定

`asyncio_mode = "auto"`，异步测试无需标记。`tests/conftest.py` 提供 `database`（临时**文件**库，不用
`:memory:`——连接池中每条连接会各自得到独立的内存库）、`store` 与 `make_runner`；`CommandRunner.run`
在真实数据库上执行 handler 并返回最终发出的消息文本，每次执行重新读取插件状态，故设置持久化可被验证。
平台请求用 `respx` 打桩，掷骰引擎用固定种子做精确断言并配合 `hypothesis` 做性质测试。
