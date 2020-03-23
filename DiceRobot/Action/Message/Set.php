<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\Customization;
use DiceRobot\Base\RobotSettings;

/**
 * Set default surface number of dice.
 */
final class Set extends AbstractAction
{
    public function __invoke(): void
    {
        $defaultSurfaceNumber = preg_replace("/^\.set[\s]*/i", "", $this->message, 1);

        if (!is_numeric($defaultSurfaceNumber))
        {
            $this->reply = Customization::getCustomReply("setDefaultSurfaceNumberError");
            return;
        }

        $defaultSurfaceNumber = intval($defaultSurfaceNumber);

        if ($defaultSurfaceNumber < 1 ||
            $defaultSurfaceNumber > Customization::getCustomSetting("maxSurfaceNumber"))
        {
            $this->reply = Customization::getCustomReply("setDefaultSurfaceNumberOverRange",
                Customization::getCustomSetting("maxSurfaceNumber"));
            return;
        }

        RobotSettings::setSetting("defaultSurfaceNumber", $defaultSurfaceNumber);
        $this->reply = Customization::getCustomReply("setDefaultSurfaceNumber", $defaultSurfaceNumber);
    }
}
