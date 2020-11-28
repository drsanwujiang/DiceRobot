<?php

declare(strict_types=1);

namespace DiceRobot\Traits\AppTraits;

use DiceRobot\Enum\AppStatusEnum;
use Psr\Log\LoggerInterface;

/**
 * Trait StatusTrait
 *
 * Application status trait.
 *
 * @package DiceRobot\Traits
 */
trait StatusTrait
{
    /** @var AppStatusEnum Current status. */
    protected AppStatusEnum $status;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /**
     * Get status of the application.
     *
     * @return AppStatusEnum Status.
     */
    public function getStatus(): AppStatusEnum
    {
        return $this->status;
    }

    /**
     * Set status of the application.
     *
     * @param AppStatusEnum $status Status.
     */
    public function setStatus(AppStatusEnum $status): void
    {
        $this->status = $status;

        if ($status->equals(AppStatusEnum::STOPPED())) {
            $this->logger->notice("Application stopped.");
        } elseif ($status->equals(AppStatusEnum::PAUSED())) {
            $this->logger->notice("Application paused.");
        } elseif ($status->equals(AppStatusEnum::RUNNING())) {
            $this->logger->notice("Application running.");
        } elseif ($status->equals(AppStatusEnum::HOLDING())) {
            $this->logger->warning("Application holding.");
        }
    }
}
