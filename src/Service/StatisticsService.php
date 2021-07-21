<?php

declare(strict_types=1);

namespace DiceRobot\Service;

use Cake\Chronos\Chronos;
use DiceRobot\Data\Report\Contact\{GroupMember, Sender};
use DiceRobot\Data\Report\Message\{FriendMessage, GroupMessage};
use DiceRobot\Data\Resource\Statistics;
use DiceRobot\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;
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
    /** @var ResourceService Resource service. */
    protected ResourceService $resource;

    /** @var RobotService Robot service. */
    protected RobotService $robot;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /** @var Statistics Statistics. */
    protected Statistics $statistics;

    /** @var int Timer ID. */
    protected int $timerId = -1;

    /** @var string[] Statistics timeline. */
    protected array $timeline;

    /** @var int[] Statistics counts. */
    protected array $counts;

    /** @var int Current statistics count. */
    protected int $count;

    /**
     * The constructor.
     *
     * @param ResourceService $resource Resource service.
     * @param RobotService $robot Robot service.
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(ResourceService $resource, RobotService $robot, LoggerFactory $loggerFactory)
    {
        $this->resource = $resource;
        $this->robot = $robot;

        $this->logger = $loggerFactory->create("Statistics");

        $this->logger->debug("Statistics service created.");
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        if ($this->timerId >= 0) {
            Timer::clear($this->timerId);
        }

        $this->logger->debug("Statistics service destructed.");
    }

    /**
     * Initialize statistics service.
     */
    public function initialize(): void
    {
        $this->statistics = $this->resource->getStatistics();

        $this->updateTimeline();
        $this->counts = array_fill(0, 6, 0);
        $this->count = $this->statistics->getInt("sum");

        // Update timeline and counts every 10 minutes
        $this->timerId = Timer::tick(600000, function () {
            $this->updateTimeline();
            array_shift($this->counts);
            $currentCount = $this->statistics->getInt("sum");
            $this->counts[] = $currentCount - $this->count;
            $this->count = $currentCount;
        });

        $this->logger->notice("Statistics service initialized.");
    }

    /**
     * Update statistics timeline.
     */
    protected function updateTimeline(): void
    {
        $now = Chronos::now();
        $format = "G:i";
        $this->timeline = [];

        foreach ([50, 40, 30, 20, 10] as $min) {
            $this->timeline[] = $now->subMinutes($min)->format($format);
        }

        $this->timeline[] = $now->format($format);
    }

    /**
     * Add order using count and friend/group ordering count.
     *
     * @param string $order Order.
     * @param string $messageType Message type.
     * @param Sender $sender Message sender.
     */
    public function addCount(string $order, string $messageType, Sender $sender): void
    {
        $this->statistics->addOrderCount($order);

        if ($messageType == FriendMessage::class) {
            $this->statistics->addFriendCount($sender->id);
        } elseif ($messageType == GroupMessage::class && $sender instanceof GroupMember) {
            $this->statistics->addGroupCount($sender->group->id);
        }

        $this->logger->info("Order using recorded.");
    }

    /**
     * Get all the statistics data.
     *
     * @return array Statistics data.
     */
    public function getAllData(): array
    {
        return $this->statistics->all();
    }

    /**
     * Get the timeline.
     *
     * @return string[] Timeline.
     */
    public function getTimeline(): array
    {
        return $this->timeline;
    }

    /**
     * Get the counts.
     *
     * @return int[] Counts.
     */
    public function getCounts(): array
    {
        return $this->counts;
    }

    /**
     * Get sorted data.
     *
     * @return array Sorted data.
     */
    public function getSortedData(): array
    {
        $statistics = $this->statistics->all();

        $data = [
            "sum" => $statistics["sum"],
            "orders" => [],
            "groups" => [],
            "friends" => [],
            "timeline" => $this->timeline,
            "counts" => $this->counts
        ];

        foreach (["orders", "groups", "friends"] as $type) {
            arsort($statistics[$type], SORT_NUMERIC);
            $statistics[$type] = array_slice($statistics[$type], 0, 5, true);
        }

        foreach ($statistics["orders"] as $order => $value) {
            $data["orders"][] = [$order, $value];
        }

        foreach ($statistics["groups"] as $id => $value) {
            $data["groups"][] = [$id, $this->robot->getGroup($id)->name ?? "[Unknown Group]", $value];
        }

        foreach ($statistics["friends"] as $id => $value) {
            $data["friends"][] = [$id, $this->robot->getFriend($id)->nickname ?? "[Unknown Friend]", $value];
        }

        return $data;
    }
}
