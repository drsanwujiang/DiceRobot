<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
use DiceRobot\Service\Customization;

/**
 * Set default surface number of dice.
 */
final class Set extends Action
{
    /**
     * @throws FileUnwritableException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.set[\s]*/i", "", $this->message, 1);

        if (!is_numeric($order))
        {
            $this->reply = Customization::getReply("setDefaultSurfaceNumberError");
            return;
        }

        $defaultSurfaceNumber = (int) $order;

        if ($defaultSurfaceNumber < 1 ||
            $defaultSurfaceNumber > Customization::getSetting("maxSurfaceNumber"))
        {
            $this->reply = Customization::getReply("setDefaultSurfaceNumberOverstep",
                Customization::getSetting("maxSurfaceNumber"));
            return;
        }

        $this->chatSettings->set("defaultSurfaceNumber", $defaultSurfaceNumber);
        $this->reply = Customization::getReply("setDefaultSurfaceNumberResult", $defaultSurfaceNumber);
    }
}
