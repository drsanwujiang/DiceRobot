<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\Message\Robot\{About, Goodbye, Nickname, Start, Stop};
use DiceRobot\Action\OrderRouterAction;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class RobotRouter
 *
 * Parse robot control order and route it to specific action.
 *
 * @order robot
 *
 *      Sample: .robot about
 *              @Robot .robot start
 *              .robot start 12345678
 *              .robot start 5678
 *              @Robot .robot stop
 *              .robot stop 12345678
 *              .robot stop 5678
 *              @Robot .robot nn
 *              .robot nn 12345678
 *              .robot nn 5678
 *              @Robot .robot nn Sakura
 *              .robot nn 12345678 Sakura
 *              .robot nn 5678 Sakura
 *              @Robot .robot goodbye
 *              .robot goodbye 12345678
 *              .robot goodbye 5678
 *
 * @package DiceRobot\Action\Message
 */
class RobotRouter extends OrderRouterAction
{
    /** @var string[] Mapping between robot order and the full name of the corresponding class. */
    protected static array $orders = [
        "about" => About::class,
        "start" => Start::class,
        "stop" => Stop::class,
        "nn" => Nickname::class,
        "goodbye" => Goodbye::class,

        "on" => Start::class,  // Alias of start
        "off" => Stop::class,  // Alias of stop
        "dismiss" => Goodbye::class,  // Alias of goodbye

        "" => About::class,  // Redirect to about by default
    ];

    /**
     * @inheritDoc
     *
     * @return bool Active flag.
     */
    public function checkActive(): bool
    {
        if (preg_match("/^(start|on)/i", $this->order)) {
            return true;
        }

        // True by default
        return $this->chatSettings->getBool("active") ?? true;
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
        if (!preg_match("/^(?:([a-z]+)(?:[\s]+(.+))?)?$/", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $match = strtolower($matches[1] ?? "");
        $subOrder = $matches[2] ?? "";

        /**
         * @var string $match Robot order match.
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
            $this->setReply("robotRouterUnknown");

            return false;
        }

        return true;
    }
}
