<?php

declare(strict_types=1);

namespace DiceRobot\Action;

use DiceRobot\Action\Message\RobotAction;
use DiceRobot\Data\Config;
use DiceRobot\Data\Report\Message;
use DiceRobot\Service\{ApiService, ResourceService, RobotService};
use Psr\Container\ContainerInterface;

/**
 * Class OrderRouterAction
 *
 * Parse order and route it to specific action.
 *
 * @package DiceRobot\Action
 */
abstract class OrderRouterAction extends MessageAction
{
    /** @var string[] Mapping between order and the full name of the corresponding class. */
    protected static array $orders = [];

    /** @var ContainerInterface Container. */
    protected ContainerInterface $container;

    /**
     * @inheritDoc
     *
     * @param ContainerInterface $container Container.
     * @param Config $config DiceRobot config.
     * @param ApiService $api API service.
     * @param ResourceService $resource Resource service.
     * @param RobotService $robot Robot service.
     * @param Message $message Message.
     * @param string $match Order match.
     * @param string $order Order.
     * @param bool $at If message sender at robot.
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
     */
    public function __invoke(): void
    {
        list($match, $subOrder) = $this->parseOrder();

        if (!$this->checkOrder($match)) {
            return;
        }

        $actionName = static::$orders[$match];

        /** @var RobotAction $orderAction */
        $orderAction = $this->container->make($actionName, [
            "message" => $this->message,
            "match" => $match,
            "order" => $subOrder,
            "at" => $this->at
        ]);

        $orderAction();

        $this->replies = $orderAction->replies;
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements.
     */
    abstract protected function parseOrder(): array;

    /**
     * Check the order.
     *
     * @param string $match The match.
     *
     * @return bool Validity.
     */
    protected function checkOrder(string $match): bool
    {
        if (!array_key_exists($match, static::$orders)) {
            return false;
        }

        return true;
    }
}
