# DiceRobot

DiceRobot，一个基于 [OneBot](https://github.com/howmanybots/onebot) 标准插件的 TRPG 骰子机器人。

目前，DiceRobot 可被部署在使用了以下框架及插件的机器人上：

* ~~使用 CoolQ 框架并加载了 [CQHTTP](https://github.com/richardchien/coolq-http-api) 插件的机器人~~
* 使用 [Mirai](https://github.com/mamoe/mirai) 框架并加载了 [CQHTTP Mirai](https://github.com/yyuueexxiinngg/cqhttp-mirai) 插件的机器人

DiceRobot 由 PHP 编写，意味着你可以随心所欲地对机器人的代码进行修改，达到你想要的效果。

## 部署

你可以使用 **DiceRobot 快速部署脚本** 和 **DiceRobot Windows 整合包** 来轻松部署 DiceRobot，使用方法非常简单！

参照 [DiceRobot 说明文档](https://docs.drsanwujiang.com/dicerobot/) 的 [部署](https://docs.drsanwujiang.com/dicerobot/deploy) 一节，5 分钟内就可以部署完毕开始使用。

## 指令

DiceRobot 实现了若干与 TRPG 有关的功能，分为游戏指令和控制指令。

详细指令说明参见 [DiceRobot 说明文档](https://docs.drsanwujiang.com/dicerobot/) 的 [指令](https://docs.drsanwujiang.com/dicerobot/order) 一节。

## 自定义

由于 PHP 的特性，你可以轻松地定制 DiceRobot 以达到你想要的目的，例如自定义回复、自定义设置、新增指令、新增检定规则、修改指令行为等。

自定义的详细说明可以参见 [DiceRobot 说明文档](https://docs.drsanwujiang.com/dicerobot/) 的 [自定义](https://docs.drsanwujiang.com/dicerobot/customization) 一节。

## 意见和建议

如果在使用过程中出现问题，请在 GitHub 提交 Issue 或者发邮件到 [drsanwujiang@gmail.com](mailto:drsanwujiang@gmail.com)。在 Issue 或邮件中请附上你发出的具体指令，以及 PHP 的错误日志：

* Linux 中一般是 `/var/log/apache2/dicerobot.error.log` 文件
* Windows 中一般是 `DiceRobot-Windows\XAMPP\apache\logss\error.log` 文件

有任何意见建议或想法，同样可以通过这两种方式告知，十分感谢。~~并没有几个人的~~摸鱼群 824668756，可以来瞎聊~

## 致谢

DiceRobot 的开发初衷是解决 [Dice!](https://github.com/w4123/Dice) 插件在使用过程中的一些小问题，以及增加了一些功能，所以基本掷骰指令照搬了 Dice! 的指令。

在此感谢 [溯回](https://github.com/w4123) 大佬开发了 Dice!，让 QQ 群跑团方便了许多！

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
