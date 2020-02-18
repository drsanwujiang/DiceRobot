<?php
/**
 * This file contains all the terminology and statements used in robot's reply. You can make any change you wish to
 * customized reply, but generally you don't need to modify the terminology.
 * You should use method Customization::getCustomReply(), which replaces {&1}, {&2}, {&3} ... with actual values, to
 * use these reply.
 */

const CUSTOM_REPLY = array(
    /** General reply and terminology, should NOT be modified. */
    "_generalOrderError" => "指令错误，无法识别！",
    "_generalFileLostError" => "相关文件丢失，无法执行该指令！",
    "_generalJSONDecodeError" => "相关文件解析失败，无法执行该指令！",
    "_BPDiceWording" => array(
        "B" => "奖励骰",
        "P" => "惩罚骰"
    ),
    "_checkLevel" => array(
        "GreatSuccess" => "大成功",
        "UltimateSuccess" => "极限成功",
        "VeryHardSuccess" => "极难成功",
        "HardSuccess" => "困难成功",
        "Success" => "成功",
        "Failure" => "失败",
        "UltimateFailure" => "极限失败",
        "GreatFailure" => "大失败"
    ),

    /** Customized reply, modify as you wish~ */
    "bindCardFormatError" => "人物卡格式错误，无法绑定！",
    "bindCardInternalError" => "致远星沦陷了QAQ！请稍后再试……",
    "bindCardPending" => "正在联络致远星，可能需要几秒钟，请稍等……",
    "bindCardPermissionDenied" => "这张人物卡不属于你哦~",
    "bindCardSuccess" => "人物卡绑定完成啦~",
    "bindCardUnbind" => "人物卡解绑成功",
    "characterCardLost" => "人物卡文件丢失了！Σ(っ°Д°;)っ",
    "checkDiceBPNumberOverRange" => "奖惩骰数量越界啦！",
    "checkDicePrivateChatPrivateCheck" => "咦？为什么要在私聊的时候暗检定",
    "checkDicePrivateCheck" => "{&1}悄悄进行了一次检定……",
    "checkDicePrivateCheckFromDiscuss" => "在讨论组({&1})中，",
    "checkDicePrivateCheckFromGroup" => "在{&1}({&2})中，",
    "checkDiceResult" => "{&1}进行了一次{&2}检定：{&3}/{&4}，{&5}",
    "checkDiceRuleDangerous" => "检定规则存在危险错误，无法执行！",
    "checkDiceRuleInvalid" => "检定规则错误，无法执行！",
    "checkDiceRuleLost" => "检定规则不存在！请使用 .setcoc 重新设定",
    "checkDiceRuleMatchFailed" => "检定规则匹配失败！此规则可能存在缺陷，请更换规则",
    "checkDiceValueNotFound" => "找不到这个属性/技能，无法检定",
    "checkDiceValueOverRange" => "宁这属性/技能连克总也挡不住啊[CQ:face,id=32]",
    "COCGenerateCardCountOverstep" => "COC 人物卡生成次数只能介于1~{&1}！",
    "COCGenerateCardHeading" => "以下是你生成的 COC {&1}版 人物卡：",
    "diceRollBecauseOf" => "由于{&1}，",
    "diceRollResult" => "{&1}骰出了：",
    "dicePrivateChatPrivateRoll" => "咦？为什么要在私聊的时候暗骰",
    "dicePrivateRoll" => "{&1}悄悄扔了一个骰子……",
    "dicePrivateRollFromDiscuss" => "在讨论组({&1})中，",
    "dicePrivateRollFromGroup" => "在{&1}({&2})中，",
    "diceWrongExpression" => "看不懂你想扔一个什么骰子啊……",
    "diceWrongNumber" => "骰子范围越界啦！",
    "DNDGenerateCardCountError" => "DND 人物卡生成次数只能是数字哦~",
    "DNDGenerateCardCountOverstep" => "DND 人物卡生成次数只能介于1~{&1}！",
    "DNDGenerateCardHeading" => "以下是你生成的 DND 人物卡：",
    "jrrpReply" => "{&1}今天的人品是……{&2}！",
    "kowtowWelcome" => "唔姆~既然你都诚心诚意的叩拜了♡那就让我「{&1}」看看你今天的虔诚值是 ———— {&2}！\n",
    "kowtowLevel1" => "哼(▼ヘ▼#)你明明一点都不虔诚，诅咒你下次超级大失败ヽ(#`Д´)ﾉ",
    "kowtowLevel2" => "只有这么一点虔诚的话，不天降惩罚于你已是恩赐了喵<(￣ ﹌ ￣)>",
    "kowtowLevel3" => "看来你的虔诚很有限的说(￣▽￣)~*不过还是勉强保佑保佑你吧( ･´ω`･ )",
    "kowtowLevel4" => "看在你还算虔诚的份上，祝你下次出现成功的几率高一点吧ヾ(✿ﾟ▽ﾟ)ノ",
    "kowtowLevel5" => "你的虔诚感动人家了呢٩(๑>◡<๑)۶祝你接下来好♡运♡连♡连哦~ヾ(✿ﾟ▽ﾟ)ノ",
    "kowtowLevel6" => "呐~ヾ(๑╹◡╹)ﾉ「{&1}」会一直陪伴在君の身边的哟~☆♪",
    "nicknameChanged" => "{&1}已将自己的昵称修改为{&2}",
    "nicknameUnset" => "{&1}解放了自己的真名",
    "robotCommandGoodbye" => "感谢与大家的相遇！隔花人远，有缘再见~",
    "robotCommandGoodbyeDenied" => "只有群主才可以使用这个指令哦~",
    "robotCommandGoodbyePrivate" => "请阁下手动删除我吧！东西流水，终解两相逢！",
    "robotCommandNicknameChanged" => "从现在起请称呼我为「{&1}」~",
    "robotCommandNicknameUnset" => "从现在起请用我的本名称呼我吧~",
    "robotCommandStart" => "呐呐~{&1}为你服务~☆♪",
    "robotCommandStop" => "休息，休息一会儿~",
    "robotCommandUnknown" => "咦？这是什么奇怪的指令？",
    "selfAddedBannedGroup" => "本群已被列入不友好群聊名单，DiceRobot 系列机器人拒绝服务。",
    "setCOCChangeRuleDenied" => "只有群主或管理员才可以使用这个指令哦~",
    "setCOCCurrentRule" => "当前检定规则：{&1}\n规则描述：{&2}\n检定等级：\n{&3}",
    "setCOCRuleChanged" => "检定规则已修改为：{&1}\n规则描述：{&2}\n检定等级：\n{&3}",
    "setCOCRuleIndexError" => "检定规则序号只能是数字哦~",
    "setDefaultSurfaceNumber" => "骰子的默认面数现在是：{&1}",
    "setDefaultSurfaceNumberError" => "骰子的面数只能是数字哦~",
    "setDefaultSurfaceNumberOverRange" => "骰子的面数只能介于1~{&1}！"
);
