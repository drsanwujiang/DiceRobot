<?php
namespace DiceRobot\Exception;

use DiceRobot\Base\Customization;

/**
 * Failed to parse the order. This exception will send reply "_generalOrderError".
 */
final class OrderErrorException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getCustomReply("_generalOrderError"));
    }
}
