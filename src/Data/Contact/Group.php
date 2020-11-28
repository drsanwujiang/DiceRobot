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
    /** @var int Group's ID. */
    public int $id;

    /** @var string Group's name. */
    public string $name;

    /** @var string Robot's permission. */
    public string $permission;
}
