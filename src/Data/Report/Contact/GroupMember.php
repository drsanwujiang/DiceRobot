<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Contact;

/**
 * Class GroupMember
 *
 * DTO. Group member.
 *
 * @package DiceRobot\Data\Report\Contact
 */
final class GroupMember extends Sender
{
    /** @var string Group member's nickname. */
    public string $memberName;

    /** @var string Group member's permission. */
    public string $permission;

    /** @var Group The group. */
    public Group $group;
}
