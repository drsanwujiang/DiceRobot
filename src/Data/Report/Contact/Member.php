<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Contact;

/**
 * Class Member
 *
 * DTO. Group member.
 *
 * @package DiceRobot\Data\Report\Contact
 */
class Member extends Sender
{
    /** @var string Operator nickname. */
    public string $memberName;

    /** @var string Operator permission. */
    public string $permission;

    /** @var Group The group. */
    public Group $group;
}
