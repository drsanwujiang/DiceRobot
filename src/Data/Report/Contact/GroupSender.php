<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Contact;

/**
 * Class GroupSender
 *
 * DTO. Group message (including temp message) sender.
 *
 * @package DiceRobot\Data\Report\Contact
 */
final class GroupSender extends Sender
{
    /** @var string Sender's group member name */
    public string $memberName;

    /** @var string Sender's permission in the group */
    public string $permission;

    /** @var Group Sender's group */
    public Group $group;
}
