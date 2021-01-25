<?php

declare(strict_types=1);

namespace DiceRobot\Data;

use DiceRobot\Traits\ArrayReaderTrait;
use Psr\Container\ContainerInterface;

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
     * @param ContainerInterface $container Container.
     * @param Resource\Config|null $config Panel config.
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function __construct(ContainerInterface $container, Resource\Config $config = null)
    {
        /** @var CustomConfig $customConfig */
        $customConfig = $container->make(CustomConfig::class);

        if ($config) {
            $this->__constructArrayReader((array) array_replace_recursive(
                DEFAULT_CONFIG,
                $config->all(),
                $customConfig->all()
            ));
        } else {
            $this->__constructArrayReader((array) array_replace_recursive(
                DEFAULT_CONFIG,
                $customConfig->all()
            ));
        }
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
        return (bool) ($this->data["strategy"][$key] ?? false);
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
        return (int) ($this->data["order"][$key] ?? -1);
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
        return (string) ($this->data["reply"][$key] ?? "");
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
        return (string) ($this->data["errMsg"][$key] ?? "");
    }
}
