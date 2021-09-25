<?php

declare(strict_types=1);

namespace DiceRobot\Data;

use DiceRobot\Traits\ArrayReaderTrait;

use const DiceRobot\DEFAULT_CONFIG;

/**
 * Class Config
 *
 * DTO. DiceRobot config.
 *
 * @package DiceRobot\Data
 */
class Config
{
    use ArrayReaderTrait;

    /**
     * The constructor.
     *
     * @param CustomConfig $customConfig Custom config.
     * @param Resource\Config|null $config Panel config.
     *
     */
    public function __construct(CustomConfig $customConfig, Resource\Config $config = null)
    {
        $this->load($customConfig, $config);
    }

    /**
     * Load panel config and custom config.
     *
     * @param CustomConfig $customConfig Custom config.
     * @param Resource\Config|null $config Panel config.
     */
    public function load(CustomConfig $customConfig, Resource\Config $config = null): void
    {
        $this->__constructArrayReader((array) array_replace_recursive(
            DEFAULT_CONFIG,
            $config ? $config->all() : [],
            $customConfig->all()
        ));
    }

    /**
     * Get strategy.
     *
     * @param string $key Strategy key.
     *
     * @return bool Strategy.
     */
    public function getStrategy(string $key): bool
    {
        return $this->getBool("strategy.{$key}");
    }

    /**
     * Get order.
     *
     * @param string $key Order key.
     *
     * @return int Order.
     */
    public function getOrder(string $key): int
    {
        return $this->getInt("order.{$key}");
    }

    /**
     * Get reply.
     *
     * @param string $key Reply key.
     *
     * @return string Reply.
     */
    public function getReply(string $key): string
    {
        return $this->getString("reply.{$key}");
    }

    /**
     * Get error message.
     *
     * @param string $key Error message key.
     *
     * @return string Error message.
     */
    public function getErrMsg(string $key): string
    {
        return $this->getString("errMsg.{$key}");
    }
}
