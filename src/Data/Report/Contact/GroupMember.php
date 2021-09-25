<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Contact;

/**
 * Class GroupMember
 *
 * DTO. Group member with full profile.
 *
 * @package DiceRobot\Data\Report\Contact
 */
final class GroupMember extends Member
{
    /** @var string Group member special title. */
    public string $specialTitle;

    /** @var int Join timestamp. */
    public int $joinTimestamp;

    /** @var int Last speak timestamp. */
    public int $lastSpeakTimestamp;

    /** @var int Remaining mute time. */
    public int $muteTimeRemaining;
}
