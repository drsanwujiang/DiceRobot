<?php
namespace DiceRobot\Base;

/**
 * Robot command action. Robot control ang manage class should extend this class and implement function __invoke().
 */
abstract class RobotCommandAction extends AbstractAction
{
    protected string $commandValue;

    public function __construct(object $eventData, string $commandValue)
    {
        parent::__construct($eventData);

        $this->commandValue = $commandValue;
    }
}
