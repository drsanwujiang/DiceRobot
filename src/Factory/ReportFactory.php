<?php

declare(strict_types=1);

namespace DiceRobot\Factory;

use DiceRobot\Data\Report\InvalidReport;
use DiceRobot\Data\Report\Event\{BotInvitedJoinGroupRequestEvent, BotJoinGroupEvent, BotLeaveEventKick, BotMuteEvent,
    BotOfflineEventActive, BotOfflineEventDropped, BotOfflineEventForce, BotOnlineEvent, BotReloginEvent,
    NewFriendRequestEvent};
use DiceRobot\Data\Report\Message\{FriendMessage, GroupMessage, TempMessage};
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
    /** @var string[] Report mapping */
    protected const REPORT_MAPPING = [
        "BotInvitedJoinGroupRequestEvent" => BotInvitedJoinGroupRequestEvent::class,
        "BotJoinGroupEvent" => BotJoinGroupEvent::class,
        "BotLeaveEventKick" => BotLeaveEventKick::class,
        "BotMuteEvent" => BotMuteEvent::class,
        "BotOfflineEventActive" => BotOfflineEventActive::class,
        "BotOfflineEventDropped" => BotOfflineEventDropped::class,
        "BotOfflineEventForce" => BotOfflineEventForce::class,
        "BotOnlineEvent" => BotOnlineEvent::class,
        "BotReloginEvent" => BotReloginEvent::class,
        "NewFriendRequestEvent" => NewFriendRequestEvent::class,

        "FriendMessage" => FriendMessage::class,
        "GroupMessage" => GroupMessage::class,
        "TempMessage" => TempMessage::class,

        "InvalidReport" => InvalidReport::class
    ];

    /**
     * Create report from JSON parsed object.
     *
     * @param object $reportData JSON parsed object
     *
     * @return Report The report
     */
    public static function create(object $reportData): Report
    {
        $type = $reportData->type ?? "InvalidReport";
        $class = static::REPORT_MAPPING[$type] ?? static::REPORT_MAPPING["InvalidReport"];

        return Convertor::toCustomInstance($reportData, $class, static::REPORT_MAPPING["InvalidReport"]);
    }
}
