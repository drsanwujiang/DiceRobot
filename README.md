# DiceRobot

[![Actions Status](https://github.com/drsanwujiang/DiceRobot/workflows/PHP%20Composer/badge.svg)](https://github.com/drsanwujiang/DiceRobot/actions)
[![Latest Stable Version](https://poser.pugx.org/drsanwujiang/dicerobot/v)](https://packagist.org/packages/drsanwujiang/dicerobot)
[![Latest Unstable Version](https://poser.pugx.org/drsanwujiang/dicerobot/v/unstable)](https://packagist.org/packages/drsanwujiang/dicerobot)
[![License](https://poser.pugx.org/drsanwujiang/dicerobot/license)](https://packagist.org/packages/drsanwujiang/dicerobot)

DiceRobot，一个基于 [Swoole](https://www.swoole.com/) 的 TRPG 骰子机器人。


## 如果你是 Master

DiceRobot 由 PHP 编写，并由 Swoole 驱动，意味着你可以在抛开传统 PHP 环境带来的包袱，享受协程和高并发带来的优势的同时，依旧随心所欲地对机器人的代码进行修改，达到你想要的效果。

目前，DiceRobot 可被部署在使用了以下框架及插件的机器人上：

* ~~使用 CoolQ 并加载了 [CQHTTP](https://github.com/richardchien/coolq-http-api) 插件的机器人~~（1.0.0 ~ 1.3.2）
* ~~使用 [Mirai](https://github.com/mamoe/mirai) 并加载了 [CQHTTP Mirai](https://github.com/yyuueexxiinngg/cqhttp-mirai) 插件的机器人~~（1.4.0）
* 使用 [Mirai](https://github.com/mamoe/mirai) 并加载了 [Mirai API HTTP](https://github.com/project-mirai/mirai-api-http) 插件的机器人（2.0.0）

### 部署

无需 Docker、Apache 等环境，没有复杂的设置，部署 DiceRobot 十分简单！

如果你追求极致简洁的 [一键部署](https://docs.drsanwujiang.com/dicerobot/deploy/how-to-deploy#onekey) ，那么只需几分钟，就可以直接使用；如果你有更复杂的需求（例如在已有环境中部署），那么可以基于我们推荐的流程进行 [手动部署](https://docs.drsanwujiang.com/dicerobot/deploy/how-to-deploy#manual) 。

### 自定义

如果现有的功能无法满足你的需要，那么你可以 [自定义](https://docs.drsanwujiang.com/dicerobot/deploy/customization) DiceRobot。

### 控制面板

我们为 DiceRobot 设计了一个功能丰富的 [控制面板](https://docs.drsanwujiang.com/dicerobot/use/panel) ，涵盖了除启动之外的几乎所有常用操作。只需要动动手指，就可以轻松管理和设置 DiceRobot。


## 如果你是使用者

### 使用

我们非常建议部署自己的机器人，不过如果觉得部署过程太麻烦，依然可以使用官方机器人：

* Sanwu Jr.（2656398864）- 稳定版机器人，7 × 24h 在线
* Sakura Tsang（3330148645）- 开发版机器人，在调试 BUG 时可能会暂时不可用

可以在 [在线机器人列表](https://tool.drsanwujiang.com/dicerobot/online) 页面查看机器人的在线状态。

### 指令

在 [指令](https://docs.drsanwujiang.com/dicerobot/use/order) 页面可以查阅所有指令的详细说明，使用 [.help](https://docs.drsanwujiang.com/dicerobot/use/order#help) 指令同样可以。


## 就这些了

有任何意见建议或想法，都可以直接在 GitHub 提交 Issue，或者发邮件联系我：[drsanwujiang@gmail.com](mailto:drsanwujiang@gmail.com) 。

~~并没有几个人的~~摸鱼群 824668756，可以来瞎聊~

玩得愉快~


## 致谢

DiceRobot 的开发初衷是解决 [Dice!](https://github.com/w4123/Dice) 插件在使用过程中的一些小问题，以及增加了一些功能，所以基本掷骰指令照搬了 Dice! 的指令，一些资源文件（例如 NameTemplate.json）也借用了 Dice! 的代码。

在此感谢 [溯洄](https://github.com/w4123) 大佬开发了 Dice!，让 QQ 群跑团方便了许多！


## 许可证

```
MIT License

Copyright (c) 2019-2020 Drsanwujiang

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

DiceRobot 采用 MIT 协议开源。Do whatever you want~
