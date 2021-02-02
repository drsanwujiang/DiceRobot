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
        "关于" => About::class,

        "start" => Start::class,
        "on" => Start::class,
        "启动" => Start::class,
        "启用" => Start::class,
        "开始" => Start::class,

        "stop" => Stop::class,
        "off" => Stop::class,
        "停用" => Stop::class,
        "停止" => Stop::class,
        "暂停" => Stop::class,

        "nn" => Nickname::class,
        "昵称" => Nickname::class,

        "goodbye" => Goodbye::class,
        "再见" => Goodbye::class,
        "退群" => Goodbye::class,

        "" => About::class,  // Redirect to about by default
    ];

    /**
     * @inheritDoc
     *
     * @return bool Active flag.
     */
    public function checkActive(): bool
    {
        if (preg_match("/^(start|on|启动|启用|开始)/i", $this->order)) {
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
        if (!preg_match("/^(?:([a-z\x{4e00}-\x{9fa5}]+)(?:[\s]+(.+))?)?$/ui", $this->order, $matches)) {
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
