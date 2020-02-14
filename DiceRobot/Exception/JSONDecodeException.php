<?php
namespace DiceRobot\Exception;

use DiceRobot\Base\Customization;

/**
 * Class JSONDecodeException
 *
 * Exception thrown when failed to decode JSON string. This exception will send reply "_generalJSONDecodeError".
 */
final class JSONDecodeException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getCustomReply("_generalJSONDecodeError"));
    }
}
