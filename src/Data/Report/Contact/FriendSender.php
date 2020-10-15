<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Contact;

/**
 * Class FriendSender
 *
 * DTO. Friend message sender.
 *
 * @package DiceRobot\Data\Report\Contact
 */
final class FriendSender extends Sender
{
    /** @var string Sender nickname */
    public string $nickname;

    /** @var string Sender remark */
    public string $remark;
}
