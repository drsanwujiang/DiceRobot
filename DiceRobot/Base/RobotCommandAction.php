<?php
namespace DiceRobot\Base;

/**
 * Class RobotCommandAction
 *
 * Parent class to all the action classes of robot control ang manage.
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
