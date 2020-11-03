<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Contact;

/**
 * Class Operator
 *
 * DTO. Group management operator.
 *
 * @package DiceRobot\Data\Report\Contact
 */
final class Operator
{
    /** @var int Operator ID */
    public int $id;

    /** @var string Operator's group member name */
    public string $memberName;

    /** @var string Operator's permission in the group */
    public string $permission;

    /** @var Group Operator's group */
    public Group $group;
}
