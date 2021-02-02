<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\Message\Deck\{Clear, Reset, Set, Show};
use DiceRobot\Action\OrderRouterAction;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class DeckRouter
 *
 * Parse deck order and route it to specific action.
 *
 * @order deck
 *
 *      Sample: .deck set FGO
 *              .deck reset
 *              .deck show
 *              .deck clear
 *
 * @package DiceRobot\Action\Message
 */
class DeckRouter extends OrderRouterAction
{
    /** @var string[] Mapping between deck order and the full name of the corresponding class. */
    protected static array $orders = [
        "set" => Set::class,
        "设置" => Set::class,

        "reset" => Reset::class,
        "重置" => Reset::class,

        "show" => Show::class,
        "展示" => Show::class,

        "clear" => Clear::class,
        "unset" => Clear::class,
        "清除" => Clear::class,
        "清空" => Clear::class
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
            $this->setReply("deckDisabled");

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
        if (!preg_match("/^([a-z\x{4e00}-\x{9fa5}]+)(?:[\s]+(.+))?$/ui", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $match = strtolower($matches[1]);
        $subOrder = $matches[2] ?? "";

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
            $this->setReply("deckRouterUnknown");

            return false;
        }

        return true;
    }
}
