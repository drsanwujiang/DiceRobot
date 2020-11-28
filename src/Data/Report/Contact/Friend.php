<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Contact;

/**
 * Class Friend
 *
 * DTO. Friend chat.
 *
 * @package DiceRobot\Data\Report\Contact
 */
final class Friend extends Sender
{
    /** @var string Friend's nickname. */
    public string $nickname;

    /** @var string Friend's remark. */
    public string $remark;
}
