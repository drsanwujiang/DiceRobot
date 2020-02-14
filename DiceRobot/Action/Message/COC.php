<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\Customization;
use DiceRobot\Base\DiceOperation;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class COC
 *
 * Action class of order ".coc". Generate character card of investigator.
 */
final class COC extends AbstractAction
{

    public function __invoke(): void
    {
        $version = 7;
        $generateCount = 1;
        $generateInDetail = false;
        $order = preg_replace("/^\.coc[\s]*/i", "", $this->message, 1);

        if ($order == "") $generateCount = 1;
        elseif (preg_match("/^[6-7]$/", $order)) $version = intval($order);
        elseif (preg_match("/^[1-9][0-9]*$/", $order)) $generateCount = intval($order);
        elseif (preg_match("/^[6-7]\s+[1-9][0-9]*$/", $order))
        {
            $orderArray = preg_split("/\s+/", $order, 2);
            $version = $orderArray[0];
            $generateCount = $orderArray[1];
        }
        elseif (preg_match("/^D$/i", $order)) $generateInDetail = true;
        elseif (preg_match("/^[6-7]\s*D/i", $order))
        {
            $version = $order[0];
            $generateInDetail = true;
        }
        else
        {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new OrderErrorException;
        }

        if ($generateCount > Customization::getCustomSetting("maxCharacterCardGenerateCount"))
        {
            $this->reply = Customization::getCustomReply("COCGenerateCardCountOverstep",
                Customization::getCustomSetting("maxCharacterCardGenerateCount"));
            return;
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $characterCardTemplate = Customization::getCustomFile(COC_CHARACTER_CARD_TEMPLATE_PATH);

        $templates = $characterCardTemplate["templates"];
        $templateItems = $characterCardTemplate["items"];
        $this->reply = Customization::getCustomReply("COCGenerateCardHeading", $version) . "\n";

        $characterCardRollingOrder[6] = array("3D6", "3D6", "3D6", "3D6", "3D6", "2D6+6", "2D6+6", "3D6+3", "1D10");
        $characterCardRollingOrder[7] = array("3D6X5", "3D6X5", "(2D6+6)X5", "3D6X5", "3D6X5", "(2D6+6)X5", "3D6X5",
                                                 "(2D6+6)X5", "3D6X5");

        for ($i = 1; $i <= $generateCount; $i++)
        {
            $rollResult = array();

            for ($j = 0; $j < 9; $j++)
            {
                $diceOperation = new DiceOperation($characterCardRollingOrder[$version][$j]);
                $rollResult[$j] = $diceOperation->rollResult;
            }

            $this->reply .= Customization::getCustomString(
                $templates["COC". $version . "AttributesTemplate"],
                reset($rollResult), next($rollResult), next($rollResult),
                next($rollResult), next($rollResult), next($rollResult),
                next($rollResult), next($rollResult), next($rollResult),
                array_sum($rollResult), array_sum($rollResult) - end($rollResult)
            );

            if ($i != $generateCount)
                $this->reply .= "\n";
        }

        if ($generateInDetail)
        {
            $sex = $this->randomDrawing($templateItems["sex"]);
            $age = (new DiceOperation("7D6+8"))->rollResult;
            $occupation = $this->randomDrawing($templateItems["occupation"]);
            $personalProfile = join("，",
                $this->randomDrawing($templateItems["personalProfile"], 3));
            $belief = $this->randomDrawing($templateItems["belief"]);
            $significantPerson = $this->randomDrawing($templateItems["significantPerson"]);
            $relationship = $this->randomDrawing($templateItems["relationship"]);
            $meaningfulLocation = $this->randomDrawing($templateItems["meaningfulLocation"]);
            $treasure = $this->randomDrawing($templateItems["treasure"]);
            $trait = $this->randomDrawing($templateItems["trait"]);

            $this->reply .= "\n" . Customization::getCustomString($templates["detailedInfoTemplate"], $sex, $age,
                    $occupation, $personalProfile, $sex, $belief, $significantPerson, $relationship,
                    $meaningfulLocation, $treasure, $trait);
        }

        $this->atSender = true;
    }

    private function randomDrawing(array &$array, int $times = 1)
    {
        $keys = array_rand($array, $times);

        if (is_int($keys))
            return $array[$keys];
        else
        {
            $returnArray = array();

            foreach ($keys as $key)
                array_push($returnArray, $array[$key]);

            return $returnArray;
        }
    }
}
