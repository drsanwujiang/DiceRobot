<?php /** @noinspection PhpUnusedPrivateFieldInspection */

declare(strict_types=1);

namespace DiceRobot\Enum;

use MyCLabs\Enum\Enum;

/**
 * Class AppStatusEnum
 *
 * Enum class. Application status enum.
 *
 * @package DiceRobot\Enum
 *
 * @method static AppStatusEnum STOPPED()
 * @method static AppStatusEnum RUNNING()
 * @method static AppStatusEnum HOLDING()
 * @method static AppStatusEnum WAITING()
 */
final class AppStatusEnum extends Enum
{
    private const STOPPED = -1;
    private const RUNNING = 0;
    private const HOLDING = 1;  // Holding for session initialization
    private const WAITING = 2;  // Waiting to be initialized
}
