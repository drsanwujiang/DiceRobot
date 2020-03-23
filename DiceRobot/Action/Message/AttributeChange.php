<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\API;
use DiceRobot\Base\CharacterCard;
use DiceRobot\Base\Customization;
use DiceRobot\Base\DiceOperation;
use DiceRobot\Base\RobotSettings;
use DiceRobot\Exception\CharacterCardException\CharacterCardNotBoundException;
use DiceRobot\Exception\OrderErrorException;

/**
 * Change investigator's attributes (HP, MP and SAN).
 */
class AttributeChange extends AbstractAction
{
    /** @noinspection PhpUnhandledExceptionInspection */
    public function __invoke(): void
    {
        $order = preg_replace("/^\./", "", $this->message, 1);

        if (!preg_match("/^(hp|mp|san)[\s]*[+-][\s]*/i", $order))
            throw new OrderErrorException;

        $cardId = RobotSettings::getCharacterCard($this->userId);

        if (empty($cardId))
            throw new CharacterCardNotBoundException();

        $characterCard = new CharacterCard($cardId);
        $characterCard->load();

        preg_match("/^(hp|mp|san)/i", $order, $attributeName);
        $attributeName = strtoupper($attributeName[0]);
        $order = preg_replace("/^(hp|mp|san)[\s]*/i", "", $order, 1);
        preg_match("/^[+-]/", $order, $addition);
        $addition = $addition[0] == "+";
        $expression = preg_replace("/^[+-][\s]*/", "", $order, 1);

        if (is_numeric($expression))
        {
            $value = (int) $expression;
            $result = $expression;

            if ($value < 0 || $value > Customization::getCustomSetting("maxAttributeChange"))
            {
                $this->reply = Customization::getCustomReply("attributeChangeValueOverstep");
                return;
            }
        }
        else
        {
            $diceOperation = new DiceOperation($expression);

            if (!$diceOperation->success || $diceOperation->reason != "")
            {
                $this->reply = Customization::getCustomReply("attributeChangeWrongExpression");
                return;
            }

            $value = $diceOperation->rollResult;
            $expression = str_replace("*", "×", $diceOperation->expression);
            $resultExpression = str_replace("*", "×", $diceOperation->toResultExpression());
            $arithmeticExpression = str_replace("*", "×", $diceOperation->toArithmeticExpression());
            $result = $expression . "=" . $resultExpression;
            $result .= $resultExpression == $arithmeticExpression ? "" : "=" . $arithmeticExpression;
            $result .= $diceOperation->rollResult == $arithmeticExpression ? "" : "=" . $diceOperation->rollResult;
        }

        $response = API::getAPICredential($this->selfId);  // Get credential

        if ($response["code"] != 0)
        {
            error_log("DiceRobot attribute change failed:\n" . "Attribute change user QQ ID: " .
                $this->userId);
            $this->noResponse();
        }

        $response = API::updateCharacterCard($this->userId, $cardId, $attributeName, $addition, $value,
            $response["data"]["credential"]);

        if ($response["code"] == -3)
        {
            $this->reply = Customization::getCustomReply("attributeChangePermissionDenied");
            return;
        }
        elseif ($response["code"] != 0)
        {
            $this->reply = Customization::getCustomReply("attributeChangeInternalError");
            return;
        }

        $afterValue = $response["data"]["after_value"];
        $characterCard->set($attributeName, $afterValue);
        $this->reply = Customization::getCustomReply("attributeChangeResult", $this->userNickname,
            $attributeName, Customization::getCustomReply("_attributeChangeWording")[$addition],
            $result, $attributeName, $afterValue);
    }
}
