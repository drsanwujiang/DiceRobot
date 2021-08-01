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
    /** @var int Group ID. */
    public int $id;

    /** @var string Group name. */
    public string $name;

    /** @var string Bot permission. */
    public string $permission;
}
