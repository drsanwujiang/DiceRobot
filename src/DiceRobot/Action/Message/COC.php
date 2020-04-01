<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action\Action;
use DiceRobot\Exception\ArithmeticExpressionErrorException;
use DiceRobot\Exception\InformativeException\DiceException\DiceNumberOverstepException;
use DiceRobot\Exception\InformativeException\DiceException\SurfaceNumberOverstepException;
use DiceRobot\Exception\InformativeException\FileLostException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use DiceRobot\Exception\InformativeException\OrderErrorException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use DiceRobot\Service\Container\Dice\Dice;
use DiceRobot\Service\Container\Reference;
use DiceRobot\Service\Customization;

/**
 * Generate character card of investigator.
 */
final class COC extends Action
{
    private const COC_GENERATE_RULE = [
        6 => [
            "3D6", "3D6", "3D6",
            "3D6", "3D6", "2D6+6",
            "2D6+6", "3D6+3", "1D10"
        ],
        7 => [
            "3D6X5", "3D6X5", "(2D6+6)X5",
            "3D6X5", "3D6X5", "(2D6+6)X5",
            "3D6X5", "(2D6+6)X5", "3D6X5"
        ],
        "age" => "7D6+8"
    ];

    /**
     * @throws ArithmeticExpressionErrorException
     * @throws DiceNumberOverstepException
     * @throws FileLostException
     * @throws JSONDecodeException
     * @throws OrderErrorException
     * @throws ReferenceUndefinedException
     * @throws SurfaceNumberOverstepException
     */
    public function __invoke(): void
    {
        $version = 7;
        $generateTime = 1;
        $detailed = false;
        $order = preg_replace("/^\.coc[\s]*/i", "", $this->message, 1);

        if ($order == "")
            $generateTime = 1;
        elseif (preg_match("/^[6-7]$/", $order))
            $version = (int) $order;
        elseif (preg_match("/^[1-9][0-9]*$/", $order))
            $generateTime = (int) $order;
        elseif (preg_match("/^[6-7]\s+[1-9][0-9]*$/", $order))
        {
            $orderArray = preg_split("/\s+/", $order, 2);
            $version = $orderArray[0];
            $generateTime = $orderArray[1];
        }
        elseif (preg_match("/^D$/i", $order))
            $detailed = true;
        elseif (preg_match("/^[6-7]\s*D/i", $order))
        {
            $version = $order[0];
            $detailed = true;
        }
        else
            throw new OrderErrorException;

        if ($generateTime > Customization::getSetting("maxCharacterCardGenerateCount"))
        {
            $this->reply = Customization::getReply("COCGenerateCardCountOverstep",
                Customization::getSetting("maxCharacterCardGenerateCount"));
            return;
        }

        $reference = new Reference("COCCharacterCardTemplate");
        $this->reply = Customization::getReply("COCGenerateCardHeading", $version) . "\n";
        $this->generate($version, $generateTime, $reference);

        if ($detailed)
            $this->generateDetail($reference);

        $this->reply = trim($this->reply);
        $this->atSender = true;
    }

    /**
     * Generate character card.
     *
     * @param int $version COC version
     * @param int $time Generate time
     * @param Reference $reference Reference
     *
     * @throws ArithmeticExpressionErrorException
     * @throws DiceNumberOverstepException
     * @throws SurfaceNumberOverstepException
     */
    private function generate(int $version, int $time, Reference $reference): void
    {
        for ($i = 1; $i <= $time; $i++)
        {
            $rollResult = array();

            for ($j = 0; $j < 9; $j++)
            {
                $dice = new Dice(self::COC_GENERATE_RULE[$version][$j]);
                $rollResult[$j] = $dice->rollResult;
            }

            $this->reply .= Customization::getString(
                $reference->get("templates")["COC". $version . "AttributesTemplate"],
                reset($rollResult), next($rollResult), next($rollResult),
                next($rollResult), next($rollResult), next($rollResult),
                next($rollResult), next($rollResult), next($rollResult),
                array_sum($rollResult), array_sum($rollResult) - end($rollResult)) . "\n";
        }
    }

    /**
     * Generate details of character card.
     *
     * @param Reference $reference Reference
     *
     * @throws ArithmeticExpressionErrorException
     * @throws DiceNumberOverstepException
     * @throws SurfaceNumberOverstepException
     */
    private function generateDetail(Reference $reference): void
    {
        $items = $reference->get("templates")["items"];

        $sex = $this->draw($items["sex"]);
        $age = (new Dice(self::COC_GENERATE_RULE["age"]))->rollResult;
        $occupation = $this->draw($items["occupation"]);
        $personalProfile = $this->draw($items["personalProfile"], 3);
        $belief = $this->draw($items["belief"]);
        $significantPerson = $this->draw($items["significantPerson"]);
        $relationship = $this->draw($items["relationship"]);
        $meaningfulLocation = $this->draw($items["meaningfulLocation"]);
        $treasure = $this->draw($items["treasure"]);
        $trait = $this->draw($items["trait"]);

        $this->reply .= Customization::getString($reference->get("templates")["detailedInfoTemplate"],
            $sex, $age, $occupation, $personalProfile, $sex, $belief, $significantPerson, $relationship,
            $meaningfulLocation, $treasure, $trait);
    }

    /**
     * Draw detail.
     *
     * @param array $array Details
     * @param int $times Draw time
     *
     * @return string Detail
     */
    private function draw(array &$array, int $times = 1): string
    {
        $keys = array_rand($array, $times);

        if (is_int($keys))
            return $array[$keys];
        else
        {
            $returnArray = [];

            foreach ($keys as $key)
                array_push($returnArray, $array[$key]);

            return join("，", $returnArray);
        }
    }
}
