<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Contact;

/**
 * Class Stranger
 *
 * DTO. Stranger sender.
 *
 * @package DiceRobot\Data\Report\Contact
 */
final class Stranger extends Sender
{
    /** @var string Stranger nickname. */
    public string $nickname;

    /** @var string Stranger remark. */
    public string $remark;
}
