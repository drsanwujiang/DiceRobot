<?php
/**
 * Default reply of DiceRobot. You should NOT modify this file, for it will be updated with DiceRobot.
 */

return [
    /** Exception reply */
    /* General */
    "_generalOrderError" => "指令错误，无法识别！",
    "_generalRepeatTimeOverstep" => "不能重复这么多次啦~",
    "_generalReferenceUndefined" => "引用的文件未定义，无法执行该指令！",
    /* Dice */
    "diceNumberOverstep" => "被骰子淹没，不知所措……",
    "diceSurfaceNumberOverstep" => "为什么会有这么多面的骰子啊(　д ) ﾟ ﾟ",
    "diceExpressionError" => "表达式无法解析！",
    /* IOService */
    "IOFileLost" => "相关文件丢失，无法执行该指令！",
    "IOFileDecodeError" => "相关文件解析失败，无法执行该指令！",
    "IOFileUnwritable" => "无法写入相关文件，请检查 DiceRobot 运行目录的权限！",
    /* APIService */
    "APINetworkError" => "无法连接到致远星，请检查星际通讯是否正常！",
    "APIInternalError" => "与致远星的联络出现问题QAQ！请稍后再试……",
    "APIJSONDecodeError" => "致远星传回了无法解析的数据(O_O)！请稍后再试……",
    "APIUnexpectedError" => "发生了预料之外的错误……",
    /* CharacterCard */
    "characterCardNotBound" => "你还没有绑定人物卡，无法执行该指令",
    "characterCardNotFound" => "这张人物卡不存在或不属于你哦~",
    "characterCardFormatInvalid" => "人物卡格式错误，请重新创建人物卡！",
    "characterCardItemNotExist" => "当前人物卡不存在该属性/技能，请重新绑定！",
    "characterCardPermissionDenied" => "没有找到当前绑定的人物卡，请重新绑定！",
    /* CheckRule */
    "checkRuleLost" => "检定规则不存在！请使用 .setcoc 重新设定",
    "checkRuleInvalid" => "检定规则错误，无法执行！",
    "checkRuleMatchFailed" => "检定规则匹配失败！此规则可能存在缺陷，请更换规则",
    "checkRuleDangerous" => "检定规则存在危险错误，无法执行！",

    /** Action reply */
    /* BindCard */
    "bindCardPending" => "正在联络致远星，可能需要几秒钟，请稍等……",
    "bindCardSuccess" => "人物卡绑定完成啦~",
    "bindCardUnbind" => "人物卡解绑成功",
    /* ChangeAttribute */
    "changeAttributeResult" => "{&1}的 {&2} {&3}了{&4}点，当前 {&5}：{&6}点",
    "changeAttributeWrongExpression" => "指令错误！属性值的变动只能是非负整数或掷骰表达式",
    "changeAttributeValueOverstep" => "属性值的变动太大啦……",
    /* Check */
    "checkResultHeading" => "{&1}进行了{&2}次{&3}检定：",
    "checkResultHeadingWithAttributes" => "{&1}(HP:{&2}/{&3} MP:{&4}/{&5} SAN:{&6}/{&7})进行了{&8}次{&9}检定：",
    "checkResult" => "{&1}/{&2}，{&3}",
    "checkPrivatelyInDiscuss" => "在讨论组({&1})中，",
    "checkPrivatelyInGroup" => "在{&1}({&2})中，",
    "checkPrivately" => "{&1}悄悄进行了{&2}次检定……",
    "checkPrivatelyInPrivate" => "咦？为什么要在私聊的时候暗检定",
    "checkValueInvalid" => "属性/技能值非法，不能进行检定！",
    "checkValueTooLarge" => "宁这属性/技能连克总也挡不住啊[CQ:face,id=32]",
    /* COC */
    "COCGenerateCardHeading" => "以下是你生成的 COC {&1}版 人物卡：",
    "COCGenerateCardCountOverstep" => "COC 人物卡生成次数只能介于1~{&1}！",
    /* DND */
    "DNDGenerateCardHeading" => "以下是你生成的 DND 人物卡：",
    "DNDGenerateCardCountOverstep" => "DND 人物卡生成次数只能介于1~{&1}！",
    /* JRRP */
    "jrrpReply" => "{&1}今天的人品是……{&2}！",
    /* Kowtow */
    "kowtowHeading" => "唔姆~既然你都诚心诚意的叩拜了♡那就让我「{&1}」看看你今天的虔诚值是 ———— {&2}！\n",
    "kowtowLevel1" => "哼(▼ヘ▼#)你明明一点都不虔诚，诅咒你下次超级大失败ヽ(#`Д´)ﾉ",
    "kowtowLevel2" => "只有这么一点虔诚的话，不天降惩罚于你已是恩赐了喵<(￣ ﹌ ￣)>",
    "kowtowLevel3" => "看来你的虔诚很有限的说(￣▽￣)~*不过还是勉强保佑保佑你吧( ･´ω`･ )",
    "kowtowLevel4" => "看在你还算虔诚的份上，祝你下次出现成功的几率高一点吧ヾ(✿ﾟ▽ﾟ)ノ",
    "kowtowLevel5" => "你的虔诚感动人家了呢٩(๑>◡<๑)۶祝你接下来好♡运♡连♡连哦~ヾ(✿ﾟ▽ﾟ)ノ",
    "kowtowLevel6" => "呐~ヾ(๑╹◡╹)ﾉ「{&1}」会一直陪伴在君の身边的哟~☆♪",
    /* Nickname*/
    "nicknameChanged" => "{&1}已将自己的昵称修改为{&2}",
    "nicknameUnset" => "{&1}解放了自己的真名",
    /* RobotCommandRouter */
    "robotCommandUnknown" => "咦？这是什么奇怪的指令？",
    /* Roll */
    "rollBecauseOf" => "由于{&1}，",
    "rollResult" => "{&1}骰出了：",
    "rollPrivatelyInDiscuss" => "在讨论组({&1})中，",
    "rollPrivatelyInGroup" => "在{&1}({&2})中，",
    "rollPrivately" => "{&1}悄悄地进行了{&2}次掷骰……",
    "rollPrivatelyInPrivate" => "咦？为什么要在私聊的时候暗骰",
    /* SanCheck */
    "sanCheckResult" => "{&1}进行了 SAN 值检定：{&2}/{&3}，{&4}\nSAN 值减少{&5}点，剩余{&6}/{&7}",
    "sanCheckValueOverstep" => "SAN 值的变动太大啦……",
    "sanCheckWrongExpression" => "指令错误！SAN 值的损失只能是非负整数或掷骰表达式",
    /* Set */
    "setDefaultSurfaceNumberResult" => "骰子的默认面数现在是：{&1}",
    "setDefaultSurfaceNumberError" => "骰子的默认面数只能是数字哦~",
    "setDefaultSurfaceNumberOverstep" => "骰子的默认面数只能介于1~{&1}！",
    /* SetCOC */
    "setCOCCurrentRule" => "当前检定规则：{&1}\n规则描述：{&2}\n检定等级：\n{&3}",
    "setCOCRuleChanged" => "检定规则已修改为：{&1}\n规则描述：{&2}\n检定等级：\n{&3}",
    "setCOCRuleIndexError" => "检定规则序号只能是数字哦~",
    "setCOCChangeRuleDenied" => "只有群主或管理员才可以使用这个指令哦~",
    /* RobotCommand */
    "robotCommandStart" => "呐呐~{&1}为你服务~☆♪",
    "robotCommandStop" => "休息，休息一会儿~",
    "robotCommandNicknameChanged" => "从现在起请称呼我为「{&1}」~",
    "robotCommandNicknameUnset" => "从现在起请用我的本名称呼我吧~",
    "robotCommandGoodbye" => "感谢与诸位的相遇！隔花人远，有缘再见~",
    "robotCommandGoodbyeDenied" => "只有群主才可以使用这个指令哦~",
    "robotCommandGoodbyePrivate" => "请手动删除我吧！东西流水，终解两相逢！",
    /* SelfAdded */
    "selfAddedBannedGroup" => "本群已被列入不友好群聊名单，DiceRobot 系列机器人拒绝服务。",
];
