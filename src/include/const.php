<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

declare(strict_types=1);

/**
 * Constants of DiceRobot.
 *
 * This file contains version (DICEROBOT_VERSION), startup time (DICEROBOT_STARTUP) and default config
 * (DEFAULT_CONFIG) of DiceRobot.
 *
 * This file should NOT be modified, for it ensures DiceRobot can work in the default mode and behave as expected.
 *
 * @package DiceRobot
 */

namespace {
    /** @var string Current version. */
    const DICEROBOT_VERSION = "2.0.3";

    /** @var string Root directory. */
    define("DICEROBOT_STARTUP", time());
}

namespace DiceRobot {
    use Monolog\Logger;

    /** @var array Default config. */
    const DEFAULT_CONFIG = [
        "dicerobot" => [
            "version" => DICEROBOT_VERSION,
            "server" => [
                "host" => "0.0.0.0",
                "port" => 9500
            ],
            "api" => [
                "prefix" => "https://api.drsanwujiang.com"
            ]
        ],

        "mirai" => [
            "server" => [
                "host" => "127.0.0.1",
                "port" => 8080
            ],
            "robot" => [
                "id" => 10000,
                "authKey" => "12345678"
            ]
        ],

        "log" => [
            "channel" => "default",
            "filename" => "dicerobot.log",
            "level" => [
                "file" => Logger::NOTICE,
                "console" => Logger::CRITICAL
            ]
        ],

        "strategy" => [
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
            "maxRepeatTimes" => 20,
            "maxReplyCharacter" => 1000
        ],

        "reply" => [
            /** Message */

            /* Card */
            "cardPending" => "正在联络致远星，可能需要几秒钟，请稍等……",
            "cardBind" => "人物卡绑定完成啦~",
            "cardUnbind" => "人物卡解绑成功",

            /* ChangeAttribute */
            "changeAttributeResult" => "{&昵称}的 {&属性} {&增减}了{&变动值}点，当前 {&属性}：{&当前值}点",
            "changeAttributeWrongExpression" => "指令错误！属性值的变动只能是非负整数或掷骰表达式",

            /* Check */
            "checkPrivate" => "{&昵称}悄悄地进行了{&检定次数}次检定……",
            "checkPrivateResult" => "在{&群名}({&群号})中，{&检定详情}",
            "checkResult" => "{&昵称}进行了{&检定次数}次{&检定项目}检定：{&检定详情}",
            "checkResultWithAttributes" => "{&昵称}(HP:{&当前HP}/{&最大HP} MP:{&当前MP}/{&最大MP} SAN:{&当前SAN}/{&最大SAN})进行了{&检定次数}次{&检定项目}检定：{&检定详情}",
            "checkDetail" => "{&掷骰结果}/{&检定值}，{&检定结果}",
            "checkPrivateNotInGroup" => "咦？为什么要在私聊的时候暗检定",
            "checkValueInvalid" => "属性/技能值非法，不能进行检定！",

            /* Coc */
            "cocGenerateResult" => "{&@发送者}以下是你生成的 COC {&COC版本}版 调查员人物卡：\n{&调查员属性}{&调查员详细信息}",
            "cocGenerateCountOverstep" => "COC 人物卡生成次数只能介于1~{&最大生成次数}！",

            /* Deck */
            "deckSet" => "默认牌堆已修改为：{&牌堆名称}",
            "deckReset" => "【嘭！】牌堆又变回了原来的样子( ﾟ▽ﾟ)/ ",
            "deckShowRest" => "当前牌堆还剩下这些卡牌：\n{&卡牌列表}",
            "deckUnset" => "牌堆不见惹~",
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

            /* Help */
            "helpOrderUnknown" => "咦？找不到这条指令诶……",

            /* Jrrp */
            "jrrpResult" => "{&昵称}今天的人品是……{&人品}！",

            /* Kowtow */
            "kowtowResult" => "[mirai:at:{&发送者QQ}] 唔姆~既然你都诚心诚意的叩拜了♡那就让我「{&机器人昵称}」看看你今天的虔诚值是 ———— {&虔诚值}！\n{&虔诚等级}",
            "kowtowLevel0" => "哼(▼ヘ▼#)你明明一点都不虔诚，诅咒你下次超级大失败ヽ(#`Д´)ﾉ",
            "kowtowLevel1" => "只有这么一点虔诚的话，不天降惩罚于你已是恩赐了喵<(￣ ﹌ ￣)>",
            "kowtowLevel2" => "看来你的虔诚很有限的说(￣▽￣)~*不过还是勉强保佑保佑你吧( ･´ω`･ )",
            "kowtowLevel3" => "看在你还算虔诚的份上，祝你下次出现成功的几率高一点吧ヾ(✿ﾟ▽ﾟ)ノ",
            "kowtowLevel4" => "你的虔诚感动人家了呢٩(๑>◡<๑)۶祝你接下来好♡运♡连♡连哦~ヾ(✿ﾟ▽ﾟ)ノ",
            "kowtowLevel5" => "呐~ヾ(๑╹◡╹)ﾉ「{&机器人昵称}」会一直陪伴在君の身边的哟~☆♪",

            /* Name */
            "nameGenerateResult" => "[mirai:at:{&发送者QQ}] 为你寻找到了这些名称：{&名称}",
            "nameGenerateCountOverstep" => "随机名称生成次数只能介于1~{&最大生成次数}！",

            /* Nickname */
            "nicknameSet" => "{&昵称}已将自己的昵称修改为{&新昵称}",
            "nicknameUnset" => "{&昵称}解放了自己的真名",

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

            /* RobotOrderRouter */
            "robotOrderUnknown" => "咦？这是什么奇怪的指令？",

            /* RobotOrder */
            "robotOrderStart" => "呐呐~{&机器人昵称}为你服务~☆♪",
            "robotOrderStartDenied" => "只有群主/管理员才可以叫醒人家哦~",
            "robotOrderStop" => "休息，休息一会儿~",
            "robotOrderStopDenied" => "但是群主/管理员还没有让人家休息呀……",
            "robotOrderNicknameSet" => "从现在起请称呼我为「{&机器人新昵称}」~",
            "robotOrderNicknameUnset" => "真·名·解·放~",
            "robotOrderGoodbye" => "期待与君の再次相遇~",
            "robotOrderGoodbyePrivate" => "请手动删除我吧！期待与君の再次相遇~",
            "robotOrderGoodbyeDenied" => "只有群主/管理员才可以让人家离开哦~",

            /** Event */

            /* BotInvitedJoinGroupRequest */
            "botInvitedJoinGroupRequestRejected" => "本群已被列入不友好群聊名单，DiceRobot 系列机器人拒绝服务。",

            /* BotJoinGroup */
            "botJoinGroupRejected" => "本群已被列入不友好群聊名单，DiceRobot 系列机器人拒绝服务。"
        ],

        "errMsg" => [
            /* General */
            "_generalOrderError" => "指令错误，无法识别！",
            "_generalRepeatTimeOverstep" => "不能重复这么多次啦~",

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
            "attributeChange" => [
                "+" => "增加",
                "-" => "减少"
            ],
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
            "sanCheckLevel" => [
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
                "robot" => \DiceRobot\Action\Message\RobotOrderRouter::class,
                "dismiss" => \DiceRobot\Action\Message\Dismiss::class,  // Alias of .robot goodbye
            ],
            20 => [
                "ra" => \DiceRobot\Action\Message\Check::class,
                "sc" => \DiceRobot\Action\Message\SanityCheck::class,

                "coc" => \DiceRobot\Action\Message\Coc::class,
                "dnd" => \DiceRobot\Action\Message\Dnd::class,

                "card" => \DiceRobot\Action\Message\Card::class,
                "hp" => \DiceRobot\Action\Message\ChangeAttribute::class,
                "mp" => \DiceRobot\Action\Message\ChangeAttribute::class,  // Alias
                "san" => \DiceRobot\Action\Message\ChangeAttribute::class,  // Alias

                "name" => \DiceRobot\Action\Message\Name::class,
                "nn" => \DiceRobot\Action\Message\Nickname::class,

                "set" => \DiceRobot\Action\Message\Set::class,

                "draw" => \DiceRobot\Action\Message\Draw::class,
                "deck" => \DiceRobot\Action\Message\Deck::class,

                "jrrp" => \DiceRobot\Action\Message\Jrrp::class,
                "orz" => \DiceRobot\Action\Message\Kowtow::class,

                "bot" =>\DiceRobot\Action\Message\RobotOrderRouter::class,  // Alias of .robot

                "help" => \DiceRobot\Action\Message\Help::class,
                "hello" => \DiceRobot\Action\Message\Hello::class
            ],
            100 => [
                "r" => \DiceRobot\Action\Message\Dicing::class,
                "w" => \DiceRobot\Action\Message\DicePool::class,
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
            \DiceRobot\Data\Report\Event\NewFriendRequestEvent::class =>
                \DiceRobot\Action\Event\NewFriendRequest::class
        ]
    ];

    /** @var array Default chat settings. */
    const DEFAULT_CHAT_SETTINGS = [
        "active" => true,
        "robotNickname" => null,

        "defaultSurfaceNumber" => null,
        "cocCheckRule" => 0,
        "defaultCardDeck" => null,
        "cardDeck" => null,

        "characterCards" => [],
        "nicknames" => []
    ];
}
