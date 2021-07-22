<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

declare(strict_types=1);

/**
 * Constants of DiceRobot.
 *
 * This file contains version (DICEROBOT_VERSION), startup time (DICEROBOT_STARTUP), default config (DEFAULT_CONFIG),
 * routes (DEFAULT_ROUTES) and chat settings (DEFAULT_CHAT_SETTINGS) of DiceRobot.
 *
 * This file should NOT be modified, for it ensures DiceRobot can work in the default mode and behave as expected.
 *
 * @package DiceRobot
 */

namespace {
    /** @var string Current version. */
    const DICEROBOT_VERSION = "3.0.0";

    /** @var int Startup time. */
    define("DICEROBOT_STARTUP", time());
}

namespace DiceRobot {
    /** @var array Default config. */
    const DEFAULT_CONFIG = [
        "dicerobot" => [
            "api" => [
                "uri" => "https://api.dicerobot.tech/v3/"
            ],
            "server" => [
                "host" => "0.0.0.0",
                "port" => 9500
            ],
            "service" => [
                "name" => "dicerobot"
            ],
            "skeleton" => [
                "uri" => "https://dl.drsanwujiang.com/dicerobot/skeleton/3.0.0/"
            ],
            "version" => DICEROBOT_VERSION
        ],

        "mirai" => [
            "server" => [
                "host" => "127.0.0.1",
                "port" => 8080
            ],
            "service" => [
                "name" => "mirai"
            ],
            "path" => "/root/mirai"
        ],

        "log" => [
            "channel" => "default",
            "filename" => "dicerobot.log",
            "level" => [
                "file" => \Monolog\Logger::NOTICE,
                "console" => \Monolog\Logger::CRITICAL
            ]
        ],

        "strategy" => [
            "enableLog" => true,
            "enableDraw" => true,
            "enableDeck" => true,
            "enableJrrp" => true,
            "enableKowtow" => true,

            "listenBotInvitedJoinGroupRequestEvent" => true,
            "approveGroupRequest" => true,
            "rejectDelinquentGroupRequest" => true,

            "listenBotJoinGroupEvent" => true,
            "quitDelinquentGroup" => true,
            "sendHelloMessage" => true,

            "listenBotMuteEvent" => false,
            "quitGroupWhenMuted" => false,

            "listenNewFriendRequestEvent" => true,
            "approveFriendRequest" => true
        ],

        "order" => [
            "maxDiceNumber" => 100,
            "maxSurfaceNumber" => 1000,
            "maxDrawCount" => 20,
            "maxGenerateCount" => 20,
            "maxRepeat" => 20,
            "maxReplyCharacter" => 1000
        ],

        "reply" => [
            /** Message */

            /* Card */
            "cardPending" => "正在联络致远星，可能需要几秒钟，请稍等……",
            "cardBind" => "人物卡绑定完成啦~",
            "cardUnbind" => "人物卡解绑成功",

            /* ChangeItem */
            "changeItemResult" => "{&昵称}的 {&属性} {&增减}了{&变动值}点，当前 {&属性}：{&当前值}点",
            "changeItemWrongExpression" => "指令错误！人物卡的变动只能是非负整数或掷骰表达式",

            /* Check */
            "checkPrivate" => "{&昵称}悄悄地进行了{&检定次数}次检定……",
            "checkPrivateResult" => "在{&群名}({&群号})中，{&检定详情}",
            "checkResult" => "{&昵称}进行了{&检定次数}次{&检定项目}检定：{&检定详情}",
            "checkResultWithStates" => "{&昵称}(HP:{&当前HP}/{&最大HP} MP:{&当前MP}/{&最大MP} SAN:{&当前SAN}/{&最大SAN})进行了{&检定次数}次{&检定项目}检定：{&检定详情}",
            "checkDetail" => "{&掷骰结果}/{&检定值}，{&检定结果}",
            "checkPrivateNotInGroup" => "咦？为什么要在私聊的时候暗检定",
            "checkValueInvalid" => "属性/技能值非法，不能进行检定！",

            /* Coc */
            "cocGenerateResult" => "{&@发送者}以下是你生成的 COC {&COC版本}版 调查员人物卡：\n{&调查员属性}{&调查员详细信息}",
            "cocGenerateCountOverstep" => "COC 人物卡生成次数只能介于1~{&最大生成次数}！",

            /* DeckRouter */
            "deckRouterUnknown" => "你要对牌堆做些什么呢？可以使用 .help deck 指令查询帮助信息哦~",

            /* Deck */
            "deckSet" => "默认牌堆已修改为：{&牌堆名称}",
            "deckReset" => "【嘭！】牌堆又变回了原来的样子( ﾟ▽ﾟ)/",
            "deckShow" => "当前牌堆还剩下这些卡牌：\n{&卡牌列表}",
            "deckClear" => "牌堆不见惹~",
            "deckDisabled" => "Master 已经禁用了 .deck (;´Д`)",
            "deckDenied" => "只有群主/管理员才可以管理默认牌堆哦~",
            "deckNotSet" => "还没有设置默认牌堆，不能这样做哦~",

            /* DicePool */
            "dicePoolResult" => "{&昵称}骰出了：{&掷骰详情}",
            "dicePoolResultWithReason" => "由于{&原因}，{&昵称}骰出了：{&掷骰详情}",
            "dicePoolThresholdOverstep" => "加骰参数只能介于5~10！",

            /* Dicing */
            "dicingPrivate" => "{&昵称}悄悄地进行了{&掷骰次数}次掷骰……",
            "dicingPrivateWithReason" => "由于{&原因}，{&昵称}悄悄地进行了{&掷骰次数}次掷骰……",
            "dicingPrivateResult" => "在{&群名}({&群号})中，{&掷骰详情}",
            "dicingResult" => "{&昵称}骰出了：{&掷骰结果}",
            "dicingResultWithReason" => "由于{&原因}，{&昵称}骰出了：{&掷骰结果}",
            "dicingPrivateNotInGroup" => "咦？为什么要在私聊的时候暗骰",

            /* Dnd */
            "dndGenerateResult" => "{&@发送者}以下是你生成的 DND 冒险者人物卡：\n{&冒险者属性}",
            "dndGenerateCountOverstep" => "DND 人物卡生成次数只能介于1~{&最大生成次数}！",

            /* Draw */
            "drawResult" => "来看看{&昵称}抽到了什么：\n{&抽牌结果}",
            "drawCountOverstep" => "一次最多只能从牌堆中抽{&最大抽牌次数}张牌哦~",
            "drawDeckEmpty" => "这副牌堆已经一张都没有了(=ﾟωﾟ)=",
            "drawDeckNotSet" => "需要先设置默认牌堆，才能愉悦地抽牌哟~",
            "drawDisabled" => "Master 已经禁用了 .draw (;´Д`)",

            /* Help */
            "helpUnknown" => "咦？找不到这条指令诶……",

            /* Jrrp */
            "jrrpResult" => "{&昵称}今天的人品是……{&人品}！",
            "jrrpDisabled" => "Master 已经禁用了 .jrrp (;´Д`)",

            /* Kowtow */
            "kowtowResult" => "[mirai:at:{&发送者QQ}] 唔姆~既然你都诚心诚意的叩拜了♡那就让我「{&机器人昵称}」看看你今天的虔诚值是 ———— {&虔诚值}！\n{&虔诚等级}",
            "kowtowLevel0" => "哼(▼ヘ▼#)你明明一点都不虔诚，诅咒你下次超级大失败ヽ(#`Д´)ﾉ",
            "kowtowLevel1" => "只有这么一点虔诚的话，不天降惩罚于你已是恩赐了喵<(￣ ﹌ ￣)>",
            "kowtowLevel2" => "看来你的虔诚很有限的说(￣▽￣)~*不过还是勉强保佑保佑你吧( ･´ω`･ )",
            "kowtowLevel3" => "看在你还算虔诚的份上，祝你下次出现成功的几率高一点吧ヾ(✿ﾟ▽ﾟ)ノ",
            "kowtowLevel4" => "你的虔诚感动人家了呢٩(๑>◡<๑)۶祝你接下来好♡运♡连♡连哦~ヾ(✿ﾟ▽ﾟ)ノ",
            "kowtowLevel5" => "呐~ヾ(๑╹◡╹)ﾉ「{&机器人昵称}」会一直陪伴在君の身边的哟~☆♪",
            "kowtowDisabled" => "Master 已经禁用了 .orz (;´Д`)",

            /* LogRouter */
            "logRouterUnknown" => "唔……这是什么指令呢？如果需要使用 Log 功能请使用 .help log 指令查询帮助信息~",

            /* Log */
            "logCreate" => "{&机器人昵称}正在忠实地记录这里所发生的一切……",
            "logStart" => "已经准备好记录了~٩(๑❛︶❛๑)۶",
            "logStop" => "收到！已经暂停记录了~(*/ω＼*)",
            "logFinish" => "【合书】终于完成啦ヾ(◍°∇°◍)ﾉﾞ\n请访问 {&Log地址} 欣赏这段独一无二的旅程吧~",
            "logExist" => "有一个 Log 正在记录中，请先完成记录的说~",
            "logNotExist" => "这里还没有正在记录中的 Log，先创建一个吧~",
            "logTempChatDenied" => "不可以在临时聊天中使用 Log 功能哦~",
            "logDisabled" => "Master 已经禁用了 .log (;´Д`)",

            /* Name */
            "nameGenerateResult" => "{&@发送者}为你寻找到了这些名称：{&名称}",
            "nameGenerateCountOverstep" => "随机名称生成次数只能介于1~{&最大生成次数}！",

            /* Nickname */
            "nicknameSet" => "{&昵称}已将自己的昵称修改为{&新昵称}",
            "nicknameUnset" => "{&昵称}解放了自己的真名",

            /* RobotRouter */
            "robotRouterUnknown" => "咦？这是什么奇怪的指令？",

            /* Robot */
            "robotStart" => "呐呐~{&机器人昵称}为你服务~☆♪",
            "robotStartDenied" => "只有群主/管理员才可以叫醒人家哦~",
            "robotStop" => "休息，休息一会儿~",
            "robotStopDenied" => "但是群主/管理员还没有让人家休息呀……",
            "robotNicknameSet" => "从现在起请称呼我为「{&机器人新昵称}」~",
            "robotNicknameUnset" => "真·名·解·放~",
            "robotGoodbye" => "期待与君の再次相遇~",
            "robotGoodbyePrivate" => "请手动删除我吧！期待与君の再次相遇~",
            "robotGoodbyeDenied" => "只有群主/管理员才可以让人家离开哦~",

            /* SanityCheck */
            "sanityCheckResult" => "{&昵称}进行了 SAN 值检定：{&掷骰结果}/{&原有SAN值}，{&检定结果}\nSAN 值减少{&SAN值减少}点，剩余{&当前SAN值}/{&最大SAN值}点",
            "sanityCheckResultWithSanity" => "{&昵称}进行了 SAN 值检定：{&掷骰结果}/{&原有SAN值}，{&检定结果}\nSAN 值减少{&SAN值减少}点，剩余{&当前SAN值}点",
            "sanityCheckWrongExpression" => "指令错误！SAN 值的损失只能是非负整数或结果非负的掷骰表达式",

            /* Set */
            "setSurfaceNumberSet" => "骰子的默认面数现在是：{&默认骰子面数}",
            "setSurfaceNumberReset" => "骰子的默认面数已重置为 100",
            "setSurfaceNumberInvalid" => "骰子的默认面数只能是介于1~{&最大骰子面数}之间的正整数哦~",

            /* SetCoc */
            "setCocCurrentRule" => "当前检定规则：{&规则名称}\n规则描述：{&规则描述}\n规则介绍：\n{&规则介绍}",
            "setCocRuleSet" => "检定规则已修改为：{&规则名称}\n规则描述：{&规则描述}\n规则介绍：\n{&规则介绍}",
            "setCocRuleIdError" => "检定规则序号只能是数字哦~",

            /** Event */

            /* BotInvitedJoinGroupRequest */
            "botInvitedJoinGroupRequestRejected" => "本群已被列入不友好群聊名单，DiceRobot 系列机器人拒绝服务。",

            /* BotJoinGroup */
            "botJoinGroupRejected" => "本群已被列入不友好群聊名单，DiceRobot 系列机器人拒绝服务。"
        ],

        "errMsg" => [
            /* General */
            "_generalOrderError" => "指令错误，无法识别！",
            "_generalRepeatOverstep" => "不能重复这么多次啦~",

            /* ApiService */
            "apiInternalError" => "与致远星的联络出现问题QAQ！请稍后再试……",
            "apiNetworkError" => "无法连接到致远星，请检查星际通讯是否正常！",
            "apiUnexpectedError" => "致远星意外地拒绝了我们的请求……",

            /* CharacterCard */
            "characterCardFormatInvalid" => "人物卡格式错误，请重新创建人物卡！",
            "characterCardItemNotExist" => "当前人物卡不存在该属性/技能，请重新绑定！",
            "characterCardLost" => "人物卡文件丢失，请重新绑定！",
            "characterCardNotBound" => "你还没有绑定人物卡，无法执行该指令",
            "characterCardNotFound" => "这张人物卡不存在或不属于你哦~",
            "characterCardPermissionDenied" => "没有找到当前绑定的人物卡，请重新绑定！",

            /* CheckRule */
            "checkRuleLost" => "检定规则不存在！请使用 .setcoc 重新设定",
            "checkRuleInvalid" => "检定规则不符合规范，无法检定！",
            "checkRuleDangerous" => "检定规则存在危险错误，无法检定！",
            "checkRuleMatchFailed" => "检定规则匹配失败！此规则可能存在缺陷，请更换规则",

            /* CardDeck */
            "cardDeckNotFound" => "啊嘞？找不到这个牌堆呀……",
            "cardDeckInvalid" => "牌堆不符合规范（子牌堆不完整），无法操作！",

            /* Dice */
            "diceNumberOverstep" => "被骰子淹没，不知所措……",
            "diceSurfaceNumberOverstep" => "为什么会有这么多面的骰子啊(　д ) ﾟ ﾟ",
            "diceExpressionError" => "掷骰表达式无法解析！",
            "diceExpressionInvalid" => "掷骰表达式不符合规则！",

            /* File */
            "fileLost" => "相关文件丢失，无法执行该指令！"
        ],

        "wording" => [
            "bpDiceType" => [
                "B" => "奖励骰",
                "P" => "惩罚骰"
            ],
            "checkLevel" => [
                "GreatSuccess" => "大成功",
                "UltimateSuccess" => "极限成功",
                "VeryHardSuccess" => "极难成功",
                "HardSuccess" => "困难成功",
                "Success" => "成功",
                "Failure" => "失败",
                "UltimateFailure" => "极限失败",
                "GreatFailure" => "大失败"
            ],
            "itemChange" => [
                "+" => "增加",
                "-" => "减少"
            ],
            "sanityCheckLevel" => [
                "success" => "成功",
                "failure" => "失败"
            ]
        ]
    ];

    /** @var array Default routes. */
    const DEFAULT_ROUTES = [
        "message" => [
            10 => [
                "setcoc" => \DiceRobot\Action\Message\SetCoc::class,
                "检定规则" => \DiceRobot\Action\Message\SetCoc::class,
                "robot" => \DiceRobot\Action\Message\RobotRouter::class,
                "机器人" => \DiceRobot\Action\Message\RobotRouter::class,
                "dismiss" => \DiceRobot\Action\Message\Dismiss::class,  // Alias of .robot goodbye
                "退群" => \DiceRobot\Action\Message\Dismiss::class
            ],
            20 => [
                "ra" => \DiceRobot\Action\Message\Check::class,
                "检定" => \DiceRobot\Action\Message\Check::class,
                "sc" => \DiceRobot\Action\Message\SanityCheck::class,
                "SAN检定" => \DiceRobot\Action\Message\SanityCheck::class,
                "理智检定" => \DiceRobot\Action\Message\SanityCheck::class,

                "coc" => \DiceRobot\Action\Message\Coc::class,
                "COC人物卡" => \DiceRobot\Action\Message\Coc::class,
                "dnd" => \DiceRobot\Action\Message\Dnd::class,
                "DND人物卡" => \DiceRobot\Action\Message\Dnd::class,

                "card" => \DiceRobot\Action\Message\Card::class,
                "人物卡" => \DiceRobot\Action\Message\Card::class,
                "hp" => \DiceRobot\Action\Message\ChangeItem::class,
                "生命" => \DiceRobot\Action\Message\ChangeItem::class,
                "mp" => \DiceRobot\Action\Message\ChangeItem::class,  // Alias
                "魔法" => \DiceRobot\Action\Message\ChangeItem::class,
                "san" => \DiceRobot\Action\Message\ChangeItem::class,  // Alias
                "理智" => \DiceRobot\Action\Message\ChangeItem::class,

                "name" => \DiceRobot\Action\Message\Name::class,
                "生成名称" => \DiceRobot\Action\Message\Name::class,
                "nn" => \DiceRobot\Action\Message\Nickname::class,
                "昵称" => \DiceRobot\Action\Message\Nickname::class,

                "set" => \DiceRobot\Action\Message\Set::class,
                "面数" => \DiceRobot\Action\Message\Set::class,
                "默认面数" => \DiceRobot\Action\Message\Set::class,
                "骰子面数" => \DiceRobot\Action\Message\Set::class,

                "log" => \DiceRobot\Action\Message\LogRouter::class,
                "记录" => \DiceRobot\Action\Message\LogRouter::class,

                "draw" => \DiceRobot\Action\Message\Draw::class,
                "抽牌" => \DiceRobot\Action\Message\Draw::class,
                "抽卡" => \DiceRobot\Action\Message\Draw::class,
                "deck" => \DiceRobot\Action\Message\DeckRouter::class,
                "牌堆" => \DiceRobot\Action\Message\DeckRouter::class,

                "jrrp" => \DiceRobot\Action\Message\Jrrp::class,
                "今日人品" => \DiceRobot\Action\Message\Jrrp::class,
                "orz" => \DiceRobot\Action\Message\Kowtow::class,
                "磕头" => \DiceRobot\Action\Message\Kowtow::class,

                "bot" =>\DiceRobot\Action\Message\RobotRouter::class,  // Alias of .robot

                "help" => \DiceRobot\Action\Message\Help::class,
                "帮助" => \DiceRobot\Action\Message\Help::class,
                "hello" => \DiceRobot\Action\Message\Hello::class,
                "欢迎" => \DiceRobot\Action\Message\Hello::class
            ],
            100 => [
                "r" => \DiceRobot\Action\Message\Dicing::class,
                "掷骰" => \DiceRobot\Action\Message\Dicing::class,
                "w" => \DiceRobot\Action\Message\DicePool::class,
                "骰池" => \DiceRobot\Action\Message\DicePool::class,
            ]
        ],

        "event" => [
            \DiceRobot\Data\Report\Event\BotInvitedJoinGroupRequestEvent::class =>
                \DiceRobot\Action\Event\BotInvitedJoinGroupRequest::class,
            \DiceRobot\Data\Report\Event\BotJoinGroupEvent::class =>
                \DiceRobot\Action\Event\BotJoinGroup::class,
            \DiceRobot\Data\Report\Event\BotLeaveEventKick::class =>
                \DiceRobot\Action\Event\BotLeaveKick::class,
            \DiceRobot\Data\Report\Event\BotMuteEvent::class =>
                \DiceRobot\Action\Event\BotMute::class,
            \DiceRobot\Data\Report\Event\BotOfflineEventActive::class =>
                \DiceRobot\Action\Event\BotOfflineActive::class,
            \DiceRobot\Data\Report\Event\BotOfflineEventDropped::class =>
                \DiceRobot\Action\Event\BotOfflineDropped::class,
            \DiceRobot\Data\Report\Event\BotOfflineEventForce::class =>
                \DiceRobot\Action\Event\BotOfflineForce::class,
            \DiceRobot\Data\Report\Event\BotOnlineEvent::class =>
                \DiceRobot\Action\Event\BotOnline::class,
            \DiceRobot\Data\Report\Event\BotReloginEvent::class =>
                \DiceRobot\Action\Event\BotRelogin::class,
            \DiceRobot\Data\Report\Event\MemberCardChangeEvent::class =>
                \DiceRobot\Action\Event\MemberCardChange::class,
            \DiceRobot\Data\Report\Event\NewFriendRequestEvent::class =>
                \DiceRobot\Action\Event\NewFriendRequest::class
        ]
    ];

    /** @var array Default chat settings. */
    const DEFAULT_CHAT_SETTINGS = [
        "active" => true,
        "robotNickname" => "",

        "defaultSurfaceNumber" => 100,
        "cocCheckRule" => 0,
        "defaultCardDeck" => "",
        "cardDeck" => null,

        "logUuid" => "",
        "isLogging" => false,

        "characterCards" => [],
        "nicknames" => []
    ];
}
