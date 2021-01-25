<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\Message\Log\{Create, Finish, Start, Stop};
use DiceRobot\Action\OrderRouterAction;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class LogRouter
 *
 * Parse TRPG log order and route it to specific action.
 *
 * @order deck
 *
 *      Sample: .log new
 *              .log start
 *              .log stop
 *              .log end
 *
 * @package DiceRobot\Action\Message
 */
class LogRouter extends OrderRouterAction
{
    /** @var string[] Mapping between TRPG log order and the full name of the corresponding class. */
    protected static array $orders = [
        "new" => Create::class,
        "start" => Start::class,
        "stop" => Stop::class,
        "end" => Finish::class,

        "on" => Start::class,  // Alias of start
        "off" => Stop::class,  // Alias of stop
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
        if (!$this->config->getStrategy("enableLog")) {
            $this->setReply("logDisabled");

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
        if (!preg_match("/^([a-z]+)$/", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $match = strtolower($matches[1]);
        $subOrder = "";

        /**
         * @var string $match Deck order match.
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
            $this->setReply("logRouterUnknown");

            return false;
        }

        return true;
    }
}
