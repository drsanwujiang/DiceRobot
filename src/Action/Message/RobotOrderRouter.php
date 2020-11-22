<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Action\Message\RobotOrder\{About, Goodbye, Nickname, Start, Stop};
use DiceRobot\Data\Config;
use DiceRobot\Data\Report\Message;
use DiceRobot\Exception\OrderErrorException;
use DiceRobot\Service\{ApiService, ResourceService, RobotService};
use Psr\Container\ContainerInterface;

/**
 * Class RobotOrderRouter
 *
 * Parse robot control order and pass it to specific action.
 *
 * @order robot
 *
 *      Sample: .robot about
 *              .robot start
 *              .robot stop
 *              .robot start 12345678
 *              .robot stop 12345678
 *              .robot start 5678
 *              .robot stop 5678
 *              .robot nn
 *              .robot nn Sakura
 *              .robot goodbye
 *              .robot goodbye 12345678
 *              .robot goodbye 5678
 *
 * @package DiceRobot\Action\Message
 */
class RobotOrderRouter extends MessageAction
{
    /** @var string[] Order mapping */
    protected const ORDER_MAPPING = [
        "about" => About::class,
        "start" => Start::class,
        "stop" => Stop::class,
        "nn" => Nickname::class,
        "goodbye" => Goodbye::class,

        "on" => Start::class,  // Alias of start
        "off" => Stop::class,  // Alias of stop
        "dismiss" => Goodbye::class,  // Alias of goodbye
    ];

    /** @var ContainerInterface Container */
    protected ContainerInterface $container;

    /**
     * @inheritDoc
     *
     * @param ContainerInterface $container
     * @param Config $config
     * @param ApiService $api
     * @param ResourceService $resource
     * @param RobotService $robot
     * @param Message $message
     * @param string $match
     * @param string $order
     * @param bool $at
     */
    public function __construct(
        ContainerInterface $container,
        Config $config,
        ApiService $apiService,
        ResourceService $dataService,
        RobotService $robotService,
        Message $message,
        string $match,
        string $order,
        bool $at
    ) {
        parent::__construct($config, $apiService, $dataService, $robotService, $message, $match, $order, $at);

        $this->container = $container;
    }

    /**
     * @inheritDoc
     *
     * @throws OrderErrorException
     */
    public function __invoke(): void
    {
        list($match, $subOrder) = $this->parseOrder();

        if (!$this->checkOrder($match)) {
            return;
        }

        $actionName = static::ORDER_MAPPING[$match];

        $orderAction = $this->container->make($actionName, [
            "message" => $this->message,
            "match" => $match,
            "order" => $subOrder,
            "at" => $this->at
        ]);

        $orderAction();

        $this->reply = $orderAction->reply;
    }

    /**
     * @inheritDoc
     *
     * @return bool Active flag
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
     * @return array Parsed elements
     *
     * @throws OrderErrorException
     */
    protected function parseOrder(): array
    {
        if (!preg_match("/^(?:([a-z]+)(?:[\s]+(.+))?)?$/", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        /** @var string $match */
        $match = $matches[1] ?? "about";  // Redirect to about by default
        /** @var string $subOrder */
        $subOrder = $matches[2] ?? "";

        return [$match, $subOrder];
    }

    /**
     * Check the order.
     *
     * @param string $match The match
     *
     * @return bool Validity
     */
    protected function checkOrder(string $match): bool
    {
        if (!array_key_exists($match, self::ORDER_MAPPING)) {
            $this->reply = $this->config->getString("reply.robotOrderUnknown");

            return false;
        }

        return true;
    }
}
