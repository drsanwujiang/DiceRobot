<?php /** @noinspection PhpUnusedPrivateFieldInspection */

declare(strict_types=1);

namespace DiceRobot\Enum;

/**
 * Class AppStatusEnum
 *
 * Enum class. Application status enum.
 *
 * @package DiceRobot\Enum
 *
 * @method static AppStatusEnum STOPPED()
 * @method static AppStatusEnum PAUSED()
 * @method static AppStatusEnum RUNNING()
 * @method static AppStatusEnum HOLDING()
 * @method static AppStatusEnum WAITING()
 */
final class AppStatusEnum extends Enum
{
    /** @var int Application stopped. */
    private const STOPPED = -2;

    /** @var int Application paused. */
    private const PAUSED = -1;

    /** @var int Application running. */
    private const RUNNING = 0;

    /** @var int Application holding, for session initialization.  */
    private const HOLDING = 1;

    /** @var int Application waiting, for self initialization. */
    private const WAITING = 2;
}
