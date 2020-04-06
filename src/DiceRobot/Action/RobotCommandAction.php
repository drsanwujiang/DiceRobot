<?php
namespace DiceRobot\Action;

use DiceRobot\Action;

/**
 * Robot command action. Robot control ang manage class should extend this class and implement function __invoke().
 */
abstract class RobotCommandAction extends Action
{
    protected string $commandValue;

    public function __construct(object $eventData, string $commandValue)
    {
        parent::__construct($eventData);

        $this->commandValue = $commandValue;
    }
}
