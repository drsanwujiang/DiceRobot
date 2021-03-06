<?php

declare(strict_types=1);

namespace DiceRobot\Data\Resource;

use DiceRobot\Data\Resource;

/**
 * Class Statistics
 *
 * Resource container. Statistics.
 *
 * @package DiceRobot\Data\Resource
 */
class Statistics extends Resource
{
    /**
     * @inheritDoc
     *
     * @param array $data Statistics data.
     */
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->data["sum"] ??= 0;
        $this->data["orders"] ??= [];
        $this->data["groups"] ??= [];
        $this->data["friends"] ??= [];
    }

    /**
     * Add using order count.
     *
     * @param string $order Order.
     */
    public function addOrderCount(string $order): void
    {
        $this->data["orders"][$order] ??= 0;
        $this->data["orders"][$order]++;
        $this->data["sum"]++;
    }

    /**
     * Add group ordering count.
     *
     * @param int $groupId Group ID.
     */
    public function addGroupCount(int $groupId): void
    {
        $this->data["groups"][$groupId] ??= 0;
        $this->data["groups"][$groupId]++;
    }

    /**
     * Add friend ordering count.
     *
     * @param int $friendId Friend ID.
     */
    public function addFriendCount(int $friendId): void
    {
        $this->data["friends"][$friendId] ??= 0;
        $this->data["friends"][$friendId]++;
    }
}
