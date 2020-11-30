<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\Message\Robot\Goodbye;

/**
 * Class Dismiss
 *
 * Quit the group.
 *
 * @order robot goodbye
 *
 *      Sample: @Robot .dismiss
 *              .dismiss 12345678
 *              .dismiss 5678
 *
 * @package DiceRobot\Action\Message
 */
class Dismiss extends Goodbye
{
}
