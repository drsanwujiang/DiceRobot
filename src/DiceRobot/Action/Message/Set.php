<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException;
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
use DiceRobot\Service\Customization;

/**
 * Set default surface number of dice.
 */
final class Set extends Action
{
    /**
     * @throws FileUnwritableException
     * @throws InformativeException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.set[\s]*/i", "", $this->message);

        if (!is_numeric($order))
            throw new InformativeException("setDefaultSurfaceNumberError");

        $defaultSurfaceNumber = (int) $order;

        if ($defaultSurfaceNumber < 1 || $defaultSurfaceNumber > Customization::getSetting("maxSurfaceNumber"))
            throw new InformativeException("setDefaultSurfaceNumberOverstep",
                Customization::getSetting("maxSurfaceNumber"));

        $this->chatSettings->set("defaultSurfaceNumber", $defaultSurfaceNumber);

        $this->reply = Customization::getReply("setDefaultSurfaceNumberResult", $defaultSurfaceNumber);
    }
}
