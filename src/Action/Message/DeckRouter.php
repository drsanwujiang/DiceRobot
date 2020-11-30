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
    /** @var string[] Mapping between order and the full name of the corresponding class. */
    protected static array $orders = [
        "set" => Set::class,
        "reset" => Reset::class,
        "show" => Show::class,
        "clear" => Clear::class,

        "unset" => Clear::class,  // Alias of clear
    ];

    /**
     * @inheritDoc
     *
     * @return array Parsed elements.
     *
     * @throws OrderErrorException
     */
    protected function parseOrder(): array
    {
        if (!preg_match("/^([a-z]+)(?:[\s]+(.+))?$/", $this->order, $matches)) {
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
