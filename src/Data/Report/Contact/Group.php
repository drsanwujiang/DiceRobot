<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Contact;

/**
 * Class Group
 *
 * DTO. Group chat.
 *
 * @package DiceRobot\Data\Report\Contact
 */
final class Group
{
    /** @var int Group's ID. */
    public int $id;

    /** @var string Group's name. */
    public string $name;

    /** @var string Robot's permission. */
    public string $permission;
}
