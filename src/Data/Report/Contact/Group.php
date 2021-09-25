<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Contact;

/**
 * Class Group
 *
 * DTO. Group.
 *
 * @package DiceRobot\Data\Report\Contact
 */
final class Group
{
    /** @var int Group ID. */
    public int $id;

    /** @var string Group name. */
    public string $name;

    /** @var string Bot permission. */
    public string $permission;
}
