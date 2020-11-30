<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Contact;

/**
 * Class Operator
 *
 * DTO. Group administration operator.
 *
 * @package DiceRobot\Data\Report\Contact
 */
final class Operator
{
    /** @var int Operator's ID. */
    public int $id;

    /** @var string Operator's nickname. */
    public string $memberName;

    /** @var string Operator's permission. */
    public string $permission;

    /** @var Group The group. */
    public Group $group;
}
