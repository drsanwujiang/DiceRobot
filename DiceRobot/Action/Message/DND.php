<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\Customization;
use DiceRobot\Base\DiceOperation;

/**
 * Generate character card of adventure.
 */
final class DND extends AbstractAction
{
    public function __invoke(): void
    {
        $generateCount = preg_replace("/^\.dnd[\s]*/i", "", $this->message, 1);

        if (!is_numeric($generateCount) && $generateCount != "")
        {
            $this->reply = Customization::getCustomReply("DNDGenerateCardCountError");
            return;
        }

        $generateCount = $generateCount == "" ? 1 : intval($generateCount);

        if ($generateCount < 1 ||
            $generateCount > Customization::getCustomSetting("maxCharacterCardGenerateCount"))
        {
            $this->reply = Customization::getCustomReply("DNDGenerateCardCountOverstep",
                Customization::getCustomSetting("maxCharacterCardGenerateCount"));
            return;
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $characterCardTemplate = Customization::getCustomFile(DND_CHARACTER_CARD_TEMPLATE_PATH);

        $this->reply = Customization::getCustomReply("DNDGenerateCardHeading") . "\n";

        for ($i = 1; $i <= $generateCount; $i++)
        {
            $rollResult = [];

            for ($j = 0; $j < 6; $j++)
            {
                $diceOperation = new DiceOperation("4D6K3");
                $rollResult[$j] = $diceOperation->rollResult;
            }

            $this->reply .= Customization::getCustomString($characterCardTemplate["DNDAttributesTemplate"],
                reset($rollResult), next($rollResult), next($rollResult),
                next($rollResult), next($rollResult), next($rollResult),
                array_sum($rollResult)
            );

            if ($i != $generateCount) $this->reply .= "\n";
        }

        $this->atSender = true;
    }
}
