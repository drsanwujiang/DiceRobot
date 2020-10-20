<?php

declare(strict_types=1);

namespace DiceRobot\Data\Resource;

use DiceRobot\Data\Resource;

/**
 * Class Statistics
 *
 * Resource container. Chat settings.
 *
 * @package DiceRobot\Data\Resource
 */
class Statistics extends Resource
{
    /**
     * @inheritDoc
     *
     * @param array $data
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
     * @param string $order
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
     * @param int $groupId
     */
    public function addGroupCount(int $groupId): void
    {
        $this->data["groups"][$groupId] ??= 0;
        $this->data["groups"][$groupId]++;
    }

    /**
     * Add friend ordering count.
     *
     * @param int $friendId
     */
    public function addFriendCount(int $friendId): void
    {
        $this->data["friends"][$friendId] ??= 0;
        $this->data["friends"][$friendId]++;
    }
}
