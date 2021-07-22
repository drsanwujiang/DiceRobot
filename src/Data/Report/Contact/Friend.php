<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Contact;

/**
 * Class Friend
 *
 * DTO. Friend sender.
 *
 * @package DiceRobot\Data\Report\Contact
 */
final class Friend extends Sender
{
    /** @var string Friend nickname. */
    public string $nickname;

    /** @var string Friend remark. */
    public string $remark;
}
