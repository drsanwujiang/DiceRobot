<?php

declare(strict_types=1);

namespace DiceRobot\Action;

use DiceRobot\App;
use DiceRobot\Data\Config;
use DiceRobot\Data\Report\Event;
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Interfaces\Action;
use DiceRobot\Service\{ApiService, ResourceService, RobotService};
use Psr\Log\LoggerInterface;

/**
 * Class EventAction
 *
 * Action that responds to event report.
 *
 * @package DiceRobot\Action
 */
abstract class EventAction implements Action
{
    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var App Application. */
    protected App $app;

    /** @var ApiService API service. */
    protected ApiService $api;

    /** @var ResourceService Resource service. */
    protected ResourceService $resource;

    /** @var RobotService Robot service. */
    protected RobotService $robot;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /** @var Event Event. */
    public Event $event;

    /**
     * The constructor.
     *
     * @param COnfig $config DiceRobot config.
     * @param App $app Application.
     * @param ApiService $api API service.
     * @param ResourceService $resource Resource service.
     * @param RobotService $robot Robot service.
     * @param LoggerFactory $loggerFactory Logger factory.
     * @param Event $event Event.
     */
    public function __construct(
        Config $config,
        App $app,
        ApiService $api,
        ResourceService $resource,
        RobotService $robot,
        LoggerFactory $loggerFactory,
        Event $event
    ) {
        $this->config = $config;
        $this->app = $app;
        $this->api = $api;
        $this->resource = $resource;
        $this->robot = $robot;
        $this->logger = $loggerFactory->create("Event");
        $this->event = $event;
    }

    /**
     * Check whether this event should be listened.
     *
     * @return bool Listen strategy.
     */
    protected function checkListen(): bool
    {
        return true;
    }
}
