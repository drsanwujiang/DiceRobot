<?php

declare(strict_types=1);

namespace DiceRobot\Factory;

use DiceRobot\Data\Report\Event\{BotGroupPermissionChangeEvent, BotInvitedJoinGroupRequestEvent, BotJoinGroupEvent,
    BotLeaveEventActive, BotLeaveEventKick, BotMuteEvent, BotOfflineEventActive, BotOfflineEventDropped,
    BotOfflineEventForce, BotOnlineEvent, BotReloginEvent, BotUnmuteEvent, FriendInputStatusChangedEvent,
    FriendNickChangedEvent, FriendRecallEvent, GroupAllowAnonymousChatEvent, GroupAllowConfessTalkEvent,
    GroupAllowMemberInviteEvent, GroupEntranceAnnouncementChangeEvent, GroupMuteAllEvent, GroupNameChangeEvent,
    GroupRecallEvent, MemberCardChangeEvent, MemberHonorChangeEvent, MemberJoinEvent, MemberJoinRequestEvent,
    MemberLeaveEventKick, MemberLeaveEventQuit, MemberMuteEvent, MemberPermissionChangeEvent,
    MemberSpecialTitleChangeEvent, MemberUnmuteEvent, NewFriendRequestEvent, NudgeEvent, OtherClientOfflineEvent,
    OtherClientOnlineEvent};
use DiceRobot\Data\Report\InvalidReport;
use DiceRobot\Data\Report\Message\{FriendMessage, GroupMessage, OtherClientMessage, StrangerMessage, TempMessage};
use DiceRobot\Interfaces\Report;
use DiceRobot\Util\Convertor;

/**
 * Class ReportFactory
 *
 * The factory of Mirai event/message report.
 *
 * @package DiceRobot\Factory
 */
class ReportFactory
{
    /** @var string[] Mapping between report and the full name of the corresponding class. */
    protected const REPORTS = [
        "FriendMessage" => FriendMessage::class,
        "GroupMessage" => GroupMessage::class,
        "OtherClientMessage" => OtherClientMessage::class,
        "StrangerMessage" => StrangerMessage::class,
        "TempMessage" => TempMessage::class,

        "BotGroupPermissionChangeEvent" => BotGroupPermissionChangeEvent::class,
        "BotInvitedJoinGroupRequestEvent" => BotInvitedJoinGroupRequestEvent::class,
        "BotJoinGroupEvent" => BotJoinGroupEvent::class,
        "BotLeaveEventActive" => BotLeaveEventActive::class,
        "BotLeaveEventKick" => BotLeaveEventKick::class,
        "BotMuteEvent" => BotMuteEvent::class,
        "BotOfflineEventActive" => BotOfflineEventActive::class,
        "BotOfflineEventDropped" => BotOfflineEventDropped::class,
        "BotOfflineEventForce" => BotOfflineEventForce::class,
        "BotOnlineEvent" => BotOnlineEvent::class,
        "BotReloginEvent" => BotReloginEvent::class,
        "BotUnmuteEvent" => BotUnmuteEvent::class,
        "FriendInputStatusChangedEvent" => FriendInputStatusChangedEvent::class,
        "FriendNickChangedEvent" => FriendNickChangedEvent::class,
        "FriendRecallEvent" => FriendRecallEvent::class,
        "GroupAllowAnonymousChatEvent" => GroupAllowAnonymousChatEvent::class,
        "GroupAllowConfessTalkEvent" => GroupAllowConfessTalkEvent::class,
        "GroupAllowMemberInviteEvent" => GroupAllowMemberInviteEvent::class,
        "GroupEntranceAnnouncementChangeEvent" => GroupEntranceAnnouncementChangeEvent::class,
        "GroupMuteAllEvent" => GroupMuteAllEvent::class,
        "GroupNameChangeEvent" => GroupNameChangeEvent::class,
        "GroupRecallEvent" => GroupRecallEvent::class,
        "MemberCardChangeEvent" => MemberCardChangeEvent::class,
        "MemberHonorChangeEvent" => MemberHonorChangeEvent::class,
        "MemberJoinEvent" => MemberJoinEvent::class,
        "MemberJoinRequestEvent" => MemberJoinRequestEvent::class,
        "MemberLeaveEventKick" => MemberLeaveEventKick::class,
        "MemberLeaveEventQuit" => MemberLeaveEventQuit::class,
        "MemberMuteEvent" => MemberMuteEvent::class,
        "MemberPermissionChangeEvent" => MemberPermissionChangeEvent::class,
        "MemberSpecialTitleChangeEvent" => MemberSpecialTitleChangeEvent::class,
        "MemberUnmuteEvent" => MemberUnmuteEvent::class,
        "NewFriendRequestEvent" => NewFriendRequestEvent::class,
        "NudgeEvent" => NudgeEvent::class,
        "OtherClientOfflineEvent" => OtherClientOfflineEvent::class,
        "OtherClientOnlineEvent" => OtherClientOnlineEvent::class,

        "InvalidReport" => InvalidReport::class
    ];

    /**
     * Create report from parsed JSON object.
     *
     * @param object $data Report data (parsed JSON object).
     *
     * @return Report The report.
     */
    public static function create(object $data): Report
    {
        $type = $data->type ?? "InvalidReport";
        $class = static::REPORTS[$type] ?? static::REPORTS["InvalidReport"];

        return Convertor::toCustomInstance($data, $class, static::REPORTS["InvalidReport"]);
    }
}
