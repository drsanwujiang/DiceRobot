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
├── trpg/      领域逻辑：掷骰引擎、十位/个位骰、检定规则（零 IO）
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
   反压。去重在入队之前，故因队列满而丢弃的事件，其重推同样被拦下，不会获得第二次机会——过载时
   宁可少答一条，也不重复回复。webhook 处理函数不得阻塞。
3. worker 取出事件 → `bot/message.py` 归一化为 `IncomingMessage` / `IncomingEvent`（剥离 `<@openid>`
   标记，统一群聊与单聊的字段结构，`ReplyTarget` 统一 `msg_id` 与 `event_id` 两种回复凭据）。
4. 被 @ 的若不含自己，直接丢弃：群里可能有多个骰子机器人，正文中的 `<@openid>` 标记会被一并剥离，
   仅凭前缀无法区分这条指令发给谁。
5. `bot/registry.py::resolve` 解析指令：要求 `.` 或 `。` 前缀，**最长别名优先**（故 `.rab` 命中奖励
   检定而非 `.ra`），重复次数写在指令之后（`.r 3#1d100`）或行尾（`.r 1d100#3`）皆可，N 限一到三位
   的正整数。`#0` 与 `#侦查` 一样并入正文：次数为零时指令一次都不执行，却照常消耗一条回复配额；
   两处都写出则按未命中处理，无从判断以谁为准。未命中即丢弃——群可能开启全量消息推送，快速路径
   开销要低。
6. 整条指令共用一个数据库会话，构造 `CommandContext` 执行 handler，退出时统一提交。
7. `bot/outbound.py`：`ReplyBuffer` 把执行期间的多段 `write` 合并成一条消息，`ReplySession` 计量
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
（`dicerobot.plugins` 组）发现；加载失败一律抛出而非静默跳过。两个例外由 loader 显式构造：`check`
需要启动时读入的检定规则，`system` 需要遍历注册表（故排在其余插件之后），它们导出的是
`build_plugin(...)` 而非 `plugin`。

- **三层开关**：会话总开关（`.bot`）→ 插件全局开关 → 插件在本会话的开关（`.plugin`）。开关类指令
  必须声明 `requires_enabled=False`，否则关闭后无法恢复；群聊中的启停仅限群主与管理员，依据
  `message.role`，未知取值一律拒绝。
- **两种输出**：`context.write` 进入本会话的被动回复；`context.write_private` 作为主动消息私聊发给
  发送者本人（暗骰即用此），只能发给发送者，不能用于群发。
- **设置以 JSON 存储**：`PluginState.settings` / `ChatPluginState.settings` 为 JSON 列，读取时由插件
  声明的 pydantic 模型校验并补齐默认值，因此增删设置项**不需要迁移数据库**。写回必须显式调用
  `save_settings` / `save_chat_settings`。
- **错误语义**：`CommandError.message` 会原样回复给玩家，措辞需面向用户；其余异常回复通用提示并记
  日志。`DEBUG=true` 时异常向上抛出而非被捕获。

## 掷骰表达式

文法是 [OneDice 标准](https://github.com/OlivOS-Team/onedice)的子集：`AdB`、`k`/`q` 取高取低、
`b`/`p` 奖惩骰、`+ - * / ^` 与括号。骰池（`a`）、双重十字（`c`）、命运骰（`f`）、多元组与循环
不做，`kh`/`kl` 一并不做——它们只在骰组与多元组上有意义，且 `6dkh` 等价于 `6dk1`，没有表达力损失。

**取低是 `q` 不是 `kl`**：`kl` 在 OneDice 中另有含义（取结果中的最小值），沿用会与生态永久打架。

奖惩骰（`trpg/percentile.py`）是 d100 的十位骰与个位骰：追加 n 个十位骰后共 n+1 个候选，奖励骰取
结果最小的，惩罚骰取最大的，个位骰只掷一次。**十位与个位都是 0 时结果为 100，故必须组合成结果之后
再比较**：个位为 0、十位掷出 0 与 9 时候选是 100 与 90，奖励骰取 90；若比十位数字会取到 0，反而
得出 100，大成功也就永远掷不出来。掷骰引擎与检定共用这一个模块。

因此奖惩骰固定 D100：面数写成 100 之外的值直接报错，也不受 `.set` 的默认骰影响。左值无意义
（OneDice 亦如此），`2b3` 报错并提示改写 `2db3`。

`^` 的规模必须在计算之前判断：`9^9^9` 一旦真的算下去会耗尽内存，故按底数位数乘指数估算，超限
即报错。

## 检定规则

规则是 `data/rules/*.json`，由机器人所有者编辑；`dicerobot/rules.py` 里的 `DEFAULT_RULES` 只是**种子**，
每次启动补齐缺失的文件（已存在的一律不动），运行时一律以文件为准。文件内的 `id` 必须与文件名一致。

奖惩骰只改变 d100 怎么掷出来，不改变判定，故 `trpg/check.py` 与规则文件都不感知它。检定的奖惩骰由
指令别名指定（`.rab` / `.rap` / `.rahb` 一类，与 OlivaDice 的 `.ra(b/p)` 一致）而非写在参数里：检定
吃的是技能值而不是表达式，参数中再放一个修饰符会与检定理由争夺开头。

个数则只能写在参数里：别名匹配是最长前缀，`.rab2 60` 剥掉 `rab` 之后是 `2 60`，与 `.rab 2 60` 同形，
两者都是两个奖励骰。数字后面还跟着数字才算个数，且取值须在 1 到 `MAX_MODIFIER_DICE` 之间，否则交回
给技能值解析——`.rab 60 2` 的 60 是技能值。

每个等级的 `condition` 是表达式，变量只有 `skill` 与 `roll`，加载时由 `trpg/check.py::compile_condition`
编译为闭包——**不用 eval**：经 AST 白名单只放行算术、比较与布尔运算，函数调用、属性访问等一律拒绝，
因而来路不明的规则文件也无法执行任意代码。运行时不再解析表达式。

加载时穷举 `skill ∈ [0,100] × roll ∈ [1,100]` 全部取值：某组取值无等级匹配即启动失败（否则要等玩家
掷出那个点数才暴露），某个等级永远匹配不到则记警告（通常是等级顺序有误）。等级按顺序取首个匹配，
所以特例必须排在通例之前。

## 数据模型要点

平台仅提供不透明的 openid：群消息中是 `member_openid`，单聊中是 `user_openid`。**实测同一用户在两
者中取值相同**，暗骰据此把群内掷出的结果私聊发给本人；但文档并未承诺这一点，故 `Chat` / `Member`
/ `ChatPluginState` 仍以 `(scene, openid…)` 为复合主键。会话与成员在首次出现时
由 `Store` 惰性创建（平台不提供成员列表），插入放在 SAVEPOINT 中：多个 worker 会同时遇到同一个新
会话，冲突的一方改取对方创建的记录，本会话的其他改动不受影响。SQLite 不支持多数
ALTER TABLE，迁移使用 `render_as_batch=True`。连接建立时开启 WAL 与 busy_timeout
（`storage/database.py`）：默认的回滚日志下，一个 worker 的读事务会挡住其他 worker 的提交。

迁移在应用启动时自动执行（`storage/migrations.py`），alembic 入口是同步的且内部自行 `asyncio.run`，
故必须 `asyncio.to_thread` 调用；配置按**当前工作目录**查找 `alembic.ini`，进程必须从项目根启动。
`migrations/env.py` 只依赖 `DATABASE_URL`，不构造完整 `Settings`——迁移不应因缺少机器人凭据而失败。

## 平台约束（改动相关代码前务必知悉）

- 群是否推送全量消息由管理员设置，两种模式互斥：开启后 @ 消息也以 `GROUP_MESSAGE_CREATE` 到达，正文
  保留 `<@openid>` 标记并带 `mentions`；关闭后才是 `GROUP_AT_MESSAGE_CREATE`，正文已由平台剥离，且
  没有 `mentions`。判断是否 @ 到自己只能用 `mentions` 中的 `is_you`：同组的 `bot` 表示对方是否为
  开放平台机器人，与「是否为本机器人」无关。
- 回复必须携带来源的 `msg_id` 或 `event_id`；两者都不传即成为主动消息。`msg_seq` 是被动回复的序号，
  主动消息一并省略（`qq/client.py::_send`）。请求体中不能出现 `null` 的这三个字段。
- 主动消息按用户计频（单聊 1000 条/用户/日），且用户可在客户端关闭，投递失败属于正常情形。暗骰用它
  把结果私聊给发起者，见 `bot/outbound.py::DirectSession`。
- access token 有效期 7200 秒，平台**仅在过期前 60 秒内**签发新的，提前刷新得到的仍是同一个。启动时
  预取 + 后台轮询刷新，401 时 `invalidate()` 后重试一次。
- Ed25519 密钥由 AppSecret 重复拼接至 32 字节派生；验签内容是 `timestamp + body`，回调校验签名内容
  是 `event_ts + plain_token`，顺序相反。
- 回调地址必须 HTTPS，端口限 80/443/8080/8443，且需在开放平台配置 IP 白名单。

## 配置

全部来自环境变量或 `.env`，不入库；嵌套字段以双下划线分隔（`QQ__APP_ID`、`LOG__LEVEL`）。
`BOT__WORKERS` 是并发槽位数而非 CPU 并行度：一条指令约 700 ms，几乎全部用于等待平台响应，所需槽位
约等于指令到达率乘以单条耗时，故默认取 32；出站连接的保活上限在 `app.py` 中与之对齐。各分节继承
`_Section`（`extra="forbid"`），字段名拼错会直接报错而非静默回退默认值。`get_settings()` 带 lru_cache，
测试需要时调用 `get_settings.cache_clear()`。

## 测试约定

`asyncio_mode = "auto"`，异步测试无需标记。`tests/conftest.py` 提供 `database`（临时**文件**库，不用
`:memory:`——连接池中每条连接会各自得到独立的内存库）、`store` 与 `make_runner`；`CommandRunner.run`
在真实数据库上执行 handler 并返回最终发出的消息文本，每次执行重新读取插件状态，故设置持久化可被验证。
平台请求用 `respx` 打桩，掷骰引擎用固定种子做精确断言并配合 `hypothesis` 做性质测试。
