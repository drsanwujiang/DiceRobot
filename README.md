# DiceRobot

DiceRobot，你的 TRPG 小助手。

## 环境

DiceRobot 需要以下环境：

- Python 3.10 或更高版本
- Poetry

## 安装

### 克隆仓库

```shell
git clone https://github.com/drsanwujiang/DiceRobot.git dicerobot
```

国内环境可以使用 Gitee 镜像

```shell
git clone https://gitee.com/drsanwujiang/DiceRobot.git dicerobot
```

### 自动安装

运行 `deploy.sh` 即可完成自动安装：

```shell
cd dicerobot
bash deploy.sh
```

自动安装脚本在以下操作系统中经过了测试：

- Debian 12（默认 Python 版本为 3.11）
- Ubuntu 24.04（默认 Python 版本为 3.12）
- Ubuntu 22.04（默认 Python 版本为 3.10）

其他操作系统建议手动安装。

### 手动安装

请参照相关文档，安装以下依赖环境：

- Python 3.10 或更高版本
- Poetry

此外还需要安装 NTQQ 相关依赖环境：

- `apt install curl xvfb libnss3 libgbm1 libasound2`
- `yum install curl xorg-x11-server-Xvfb libgbm alsa-lib-devel nss dbus-libs at-spi2-atk gtk3 cups-libs`

## 管理

在 [DiceRobot 控制面板](https://panel.dicerobot.tech/) 可以对 DiceRobot 进行管理，并且支持一键安装 NapCat 和 QQ，上手体验十分简单。
