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
    /** @var int Friend's ID. */
    public int $id;

    /** @var string Friend's nickname. */
    public string $nickname;

    /** @var string Friend's remark. */
    public string $remark;
}
