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
    /** @var int Operator ID. */
    public int $id;

    /** @var string Operator nickname. */
    public string $memberName;

    /** @var string Operator permission. */
    public string $permission;

    /** @var Group The group. */
    public Group $group;
}
