<?php

declare(strict_types=1);

namespace DiceRobot\Action;

use DiceRobot\Action\Message\RobotAction;
use DiceRobot\Data\Config;
use DiceRobot\Data\Report\Message;
use DiceRobot\Factory\LoggerFactory;
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
    private ContainerInterface $container;

    /**
     * @inheritDoc
     *
     * @param ContainerInterface $container Container.
     */
    public function __construct(
        ContainerInterface $container,
        Config $config,
        ApiService $apiService,
        ResourceService $dataService,
        RobotService $robotService,
        LoggerFactory $loggerFactory,
        Message $message,
        string $match,
        string $order,
        bool $at
    ) {
        parent::__construct(
            $config,
            $apiService,
            $dataService,
            $robotService,
            $loggerFactory,
            $message,
            $match,
            $order,
            $at
        );

        $this->container = $container;
    }

    /**
     * @inheritDoc
     *
     * @noinspection PhpUnhandledExceptionInspection
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

        $orderAction->initialize();
        $orderAction();

        $this->replies = $orderAction->replies;
    }

    /**
     * Check the order.
     *
     * @param string $match The match.
     *
     * @return bool Validity.
     */
    abstract protected function checkOrder(string $match): bool;
}
