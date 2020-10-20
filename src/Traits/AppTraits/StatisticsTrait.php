<?php

declare(strict_types=1);

namespace DiceRobot\Traits\AppTraits;

use Cake\Chronos\Chronos;
use DiceRobot\Data\Report\Contact\{GroupSender, Sender};
use DiceRobot\Data\Report\Message\{FriendMessage, GroupMessage};
use DiceRobot\Data\Resource\Statistics;
use DiceRobot\Service\ResourceService;
use Swoole\Timer;

/**
 * Trait StatisticsTrait
 *
 * The application statistics trait.
 *
 * @package DiceRobot\Traits\AppTraits
 */
trait StatisticsTrait
{
    /** @var ResourceService Data service */
    protected ResourceService $resource;

    /** @var Statistics Statistics */
    protected Statistics $statistics;

    /** @var string[] Statistics timeline */
    protected array $statisticsTimeline;

    /** @var int[] Statistics counts */
    protected array $statisticsCounts;

    /** @var int Current statistics count */
    protected int $currentCount;

    /**
     * Initialize statistics.
     */
    protected function initializeStatistics(): void
    {
        $this->statistics = $this->resource->getStatistics();
        $this->updateTimeline();
        $this->statisticsCounts = array_fill(0, 6, 0);
        $this->currentCount = $this->statistics->getInt("sum");

        Timer::tick(600000, function () {
            $this->updateTimeline();
            array_shift($this->statisticsCounts);
            $currentCount = $this->statistics->getInt("sum");
            $this->statisticsCounts[] = $currentCount - $this->currentCount;
            $this->currentCount = $currentCount;
        });
    }

    /**
     * Update statistics timeline.
     */
    protected function updateTimeline(): void
    {
        $now = Chronos::now();
        $format = "G:i";

        $this->statisticsTimeline = [
            $now->subMinutes(50)->format($format),
            $now->subMinutes(40)->format($format),
            $now->subMinutes(30)->format($format),
            $now->subMinutes(20)->format($format),
            $now->subMinutes(10)->format($format),
            $now->format($format)
        ];
    }

    /**
     * Add order using count and friend/group ordering count.
     *
     * @param string $order
     * @param string $messageType
     * @param Sender $sender
     */
    protected function addCount(string $order, string $messageType, Sender $sender): void
    {
        $this->statistics->addOrderCount($order);

        if ($messageType == FriendMessage::class)
            $this->statistics->addFriendCount($sender->id);
        elseif ($messageType == GroupMessage::class && $sender instanceof GroupSender)
            $this->statistics->addGroupCount($sender->group->id);
    }
}
