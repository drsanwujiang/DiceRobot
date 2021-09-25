<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\Message\PutBack\{Put, Set};
use DiceRobot\Action\OrderRouterAction;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class PutBackRouter
 *
 * Parse put back order and route it to specific action.
 *
 * @order put
 *
 *      Sample: .put set 12345678
 *              .put top Card
 *              .put bottom Card
 *
 * @package DiceRobot\Action\Message
 */
class PutBackRouter extends OrderRouterAction
{
    /** @var string[] Mapping between robot order and the full name of the corresponding class. */
    protected static array $orders = [
        "set" => Set::class,
        "设置" => Set::class,
        "设定" => Set::class,

        "top" => Put::class,
        "顶" => Put::class,
        "牌顶" => Put::class,
        "牌堆顶" => Put::class,
        "顶部" => Put::class,

        "bottom" => Put::class,
        "btm" => Put::class,
        "底" => Put::class,
        "牌底" => Put::class,
        "牌堆底" => Put::class,
        "底部" => Put::class,
    ];

    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        if (!$this->checkEnabled()) {
            return;
        }

        parent::__invoke();
    }

    /**
     * @inheritDoc
     *
     * @return bool Enabled.
     */
    protected function checkEnabled(): bool
    {
        if (!$this->config->getStrategy("enableDeck")) {
            $this->setReply("putBackDisabled");

            return false;
        } else {
            return true;
        }
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements.
     *
     * @throws OrderErrorException Order is invalid.
     */
    protected function parseOrder(): array
    {
        if (!preg_match("/^([a-z\x{4e00}-\x{9fa5}]+)(?:[\s]+([\S\s]+))?$/ui", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $match = strtolower($matches[1]);
        $subOrder = $matches[2] ?? "";

        /**
         * @var string $match Put back order match.
         * @var string $subOrder Sub-order.
         */
        return [$match, $subOrder];
    }

    /**
     * @inheritDoc
     *
     * @return bool Validity.
     */
    protected function checkOrder(string $match): bool
    {
        if (!array_key_exists($match, static::$orders)) {
            $this->setReply("putBackRouterUnknown");

            return false;
        }

        return true;
    }
}
