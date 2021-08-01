<?php

declare(strict_types=1);

namespace DiceRobot\Data\Contact;

/**
 * Class Friend
 *
 * DTO. QQ friend.
 *
 * @package DiceRobot\Data\Contact
 */
class Friend
{
    /** @var int Friend ID. */
    public int $id;

    /** @var string Friend nickname. */
    public string $nickname;

    /** @var string Friend remark. */
    public string $remark;
}
