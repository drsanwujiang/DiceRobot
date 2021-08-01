<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Contact;

/**
 * Class GroupMember
 *
 * DTO. Group member sender.
 *
 * @package DiceRobot\Data\Report\Contact
 */
final class GroupMember extends Sender
{
    /** @var string Group member nickname. */
    public string $memberName;

    /** @var string Group member special title. */
    public string $specialTitle;

    /** @var string Group member permission. */
    public string $permission;

    /** @var int Join timestamp. */
    public int $joinTimestamp;

    /** @var int Last speak timestamp. */
    public int $lastSpeakTimestamp;

    /** @var int Remaining mute time. */
    public int $muteTimeRemaining;

    /** @var Group The group. */
    public Group $group;
}
