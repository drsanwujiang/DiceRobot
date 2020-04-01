<?php
namespace DiceRobot\Exception\InformativeException\APIException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Character card can not be accessed. This exception will send reply "_generalCharacterCardPermissionDenied".
 */
final class PermissionDeniedException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("_generalCharacterCardPermissionDenied"));
    }
}
