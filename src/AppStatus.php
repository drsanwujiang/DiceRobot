<?php

declare(strict_types=1);

namespace DiceRobot;

use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Class AppStatus
 *
 * Application status.
 *
 * @package DiceRobot
 */
class AppStatus
{
    /** @var AppStatusEnum Application status. */
    protected static AppStatusEnum $status;

    /** @var LoggerInterface Logger. */
    protected static LoggerInterface $logger;

    /**
     * Set logger and default status.
     *
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public static function initialize(LoggerFactory $loggerFactory): void {
        self::$logger = $loggerFactory->create("Application");
        self::$status = AppStatusEnum::HOLDING();
    }

    /**
     * Set application status holding.
     */
    public static function hold(): void {
        self::$status = AppStatusEnum::HOLDING();

        self::$logger->warning("Application holding.");
    }

    /**
     * Set application status running.
     */
    public static function run(): void {
        self::$status = AppStatusEnum::RUNNING();

        self::$logger->notice("Application running.");
    }

    /**
     * Set application status paused.
     */
    public static function pause(): void {
        self::$status = AppStatusEnum::PAUSED();

        self::$logger->notice("Application paused.");
    }

    /**
     * Set application status stopped.
     */
    public static function stop(): void {
        self::$status = AppStatusEnum::STOPPED();

        self::$logger->notice("Application stopped.");
    }

    /**
     * Get application status.
     *
     * @return AppStatusEnum Status.
     */
    public static function getStatus(): AppStatusEnum
    {
        return self::$status;
    }
}
