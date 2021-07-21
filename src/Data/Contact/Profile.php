<?php

declare(strict_types=1);

namespace DiceRobot\Data\Contact;

/**
 * Class Profile
 *
 * DTO. Subject profile.
 *
 * @package DiceRobot\Data\Contact
 */
abstract class Profile
{
    /** @var string Subject nickname. */
    public string $nickname;

    /** @var string Subject e-mail address. */
    public string $email;

    /** @var int Subject age. */
    public int $age;

    /** @var int Subject QQ account level. */
    public int $level;

    /** @var string Subject QQ signature. */
    public string $sign;

    /** @var string Subject sex. */
    public string $sex;
}
