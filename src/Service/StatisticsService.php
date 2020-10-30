<?php

declare(strict_types=1);

namespace DiceRobot\Service;

use Cake\Chronos\Chronos;
use DiceRobot\Data\Report\Contact\{GroupSender, Sender};
use DiceRobot\Data\Report\Message\{FriendMessage, GroupMessage};
use DiceRobot\Data\Resource\Statistics;
use Swoole\Timer;

/**
 * Class StatisticsService
 *
 * Statistics service.
 *
 * @package DiceRobot\Service
 */
class StatisticsService
{
    /** @var ResourceService Data service */
    protected ResourceService $resource;

    /** @var Statistics Statistics */
    protected Statistics $statistics;

    /** @var string[] Statistics timeline */
    protected array $timeline;

    /** @var int[] Statistics counts */
    protected array $counts;

    /** @var int Current statistics count */
    protected int $count;

    /**
     * The constructor.
     *
     * @param ResourceService $resource
     */
    public function __construct(ResourceService $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Initialize statistics service.
     *
     * @return bool
     */
    public function initialize(): bool
    {
        $this->statistics = $this->resource->getStatistics();

        $this->updateTimeline();
        $this->counts = array_fill(0, 6, 0);
        $this->count = $this->statistics->getInt("sum");

        // Update timeline and counts every 10 minutes
        Timer::tick(600000, function () {
            $this->updateTimeline();
            array_shift($this->counts);
            $currentCount = $this->statistics->getInt("sum");
            $this->counts[] = $currentCount - $this->count;
            $this->count = $currentCount;
        });

        return true;
    }

    /**
     * Update statistics timeline.
     */
    protected function updateTimeline(): void
    {
        $now = Chronos::now();
        $format = "G:i";

        $this->timeline = [
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
    public function addCount(string $order, string $messageType, Sender $sender): void
    {
        $this->statistics->addOrderCount($order);

        if ($messageType == FriendMessage::class) {
            $this->statistics->addFriendCount($sender->id);
        } elseif ($messageType == GroupMessage::class && $sender instanceof GroupSender) {
            $this->statistics->addGroupCount($sender->group->id);
        }
    }

    /**
     * Get all the statistics data.
     *
     * @return array
     */
    public function getAllData(): array
    {
        return $this->statistics->all();
    }

    /**
     * Get timeline.
     *
     * @return string[]
     */
    public function getTimeline(): array
    {
        return $this->timeline;
    }

    /**
     * Get counts.
     *
     * @return int[]
     */
    public function getCounts(): array
    {
        return $this->counts;
    }
}
