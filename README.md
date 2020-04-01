# DiceRobot

DiceRobot，一个基于 [coolq-http-api](https://github.com/richardchien/coolq-http-api) 插件的 TRPG 骰子机器人。

## 搭建指南

由于基于 [coolq-http-api](https://github.com/richardchien/coolq-http-api) 插件，并用 PHP 编写而成，所以服务器端需要搭建 CoolQ(Wine)、Web 服务器和 PHP 环境（PHP 7.4 或更高）。

详细搭建步骤参见 [DiceRobot 说明文档](https://docs.drsanwujiang.com/dicerobot/) 的 [搭建](https://docs.drsanwujiang.com/dicerobot/build) 一节。

## 指令

DiceRobot 实现了若干与 TRPG 有关的功能，分为游戏指令和控制指令。

详细指令说明参见 [DiceRobot 说明文档](https://docs.drsanwujiang.com/dicerobot/) 的 [指令](https://docs.drsanwujiang.com/dicerobot/order) 一节。

## 自定义

由于 PHP 的特性，你可以轻松地定制 DiceRobot 以达到你想要的目的，例如自定义回复、自定义设置、新增指令、新增检定规则、修改指令行为等。

自定义的详细说明可以参见 [DiceRobot 说明文档](https://docs.drsanwujiang.com/dicerobot/) 的 [自定义](https://docs.drsanwujiang.com/dicerobot/customization) 一节。

## 意见建议和想法

如果在使用过程中出现问题，可以直接在 Github 提交 Issue，或者发邮件联系我：[drsanwujiang@gmail.com](mailto:drsanwujiang@gmail.com)。

在 Issue 或邮件中请附上你发出的具体指令，以及 PHP 的错误日志（一般是 `/var/log/apache2/dicerobot.error.log`）。

有任何意见建议或想法，同样可以通过这两种方式告知，十分感谢。摸鱼群 824668756，可以来瞎聊~

## 致谢

DiceRobot 的开发初衷是解决 [溯洄w4123](https://github.com/w4123) 大佬的 [Dice!](https://github.com/w4123/Dice) 插件在使用过程中的一些小问题，以及增加自定义的功能，所以基本掷骰指令照搬了 Dice! 的指令。在此感谢大佬开发的插件，让 QQ 群跑团方便了许多！

大佬的 [Dice! V3](https://github.com/w4123/Dice3) 也已经基本开发完成了，冲冲冲！

## License

DiceRobot is licensed under the MIT license, do whatever you want~

See [License File](https://github.com/drsanwujiang/DiceRobot/blob/master/LICENSE) for more information.