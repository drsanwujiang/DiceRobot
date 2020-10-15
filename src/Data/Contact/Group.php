<?php

declare(strict_types=1);

namespace DiceRobot\Data\Contact;

/**
 * Class Group
 *
 * DTO. QQ group.
 *
 * @package DiceRobot\Data\Contact
 */
class Group
{
    /** @var int Group ID */
    public int $id;

    /** @var string Group name */
    public string $name;

    /** @var string Robot's permission in the group */
    public string $permission;
}
