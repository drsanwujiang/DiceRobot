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
 * Sanity check.
 */
class SanCheck extends AbstractAction
{
    /** @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpUndefinedMethodInspection
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.sc[\s]*/i", "", $this->message, 1);

        if (!preg_match("/^[\S]+\/[\S]+$/i", $order))
            throw new OrderErrorException;

        $cardId = RobotSettings::getCharacterCard($this->userId);

        if (empty($cardId))
            throw new CharacterCardNotBoundException();

        $characterCard = new CharacterCard($cardId);
        $characterCard->load();

        $orders = explode("/", $order, 2);
        $diceOperations = [];
        $decreases = [];
        $decreaseResults = [];

        for ($i = 0; $i <= 1; $i++)
        {
            if (is_numeric($orders[$i]))
            {
                $decreases[$i] = (int) $orders[$i];
                $decreaseResults[$i] = $orders[$i];

                if ($decreases[$i] >= 0)
                    continue;
                else
                {
                    $this->reply = Customization::getCustomReply("sanCheckWrongExpression");
                    return;
                }
            }

            $diceOperations[$i] = new DiceOperation(trim($orders[$i]));

            if (!$diceOperations[$i]->success || $diceOperations[$i]->reason != "")
            {
                $this->reply = Customization::getCustomReply("sanCheckWrongExpression");
                return;
            }

            $decreases[$i] = $diceOperations[$i]->rollResult;
            $expression = str_replace("*", "×", $diceOperations[$i]->expression);
            $resultExpression = str_replace("*", "×", $diceOperations[$i]->toResultExpression());
            $arithmeticExpression = str_replace("*", "×",
                $diceOperations[$i]->toArithmeticExpression());
            $decreaseResults[$i] = $expression . "=" . $resultExpression;
            $decreaseResults[$i] .= $resultExpression == $arithmeticExpression ? "" : "=" . $arithmeticExpression;
            $decreaseResults[$i] .= $diceOperations[$i]->rollResult == $arithmeticExpression ?
                "" : "=" . $diceOperations[$i]->rollResult;
        }

        $diceOperations[2] = new DiceOperation(); // Check dice
        $checkResult = $diceOperations[2]->rollResult;

        $response = API::getAPICredential($this->selfId);  // Get credential

        if ($response["code"] != 0)
        {
            error_log("DiceRobot sanity check failed:\n" . "Sanity check user QQ ID: " . $this->userId);
            $this->noResponse();
        }

        $response = API::sanityCheck($this->userId, $cardId, $checkResult, $decreases, $response["data"]["credential"]);

        if ($response["code"] == -3)
        {
            $this->reply = Customization::getCustomReply("sanCheckPermissionDenied");
            return;
        }
        elseif ($response["code"] != 0)
        {
            $this->reply = Customization::getCustomReply("sanCheckInternalError");
            return;
        }

        $checkSuccess = $response["data"]["check_success"];
        $beforeSanity = $response["data"]["before_sanity"];
        $afterSanity = $response["data"]["after_sanity"];
        $maxSanity = 99 - $characterCard->get("克苏鲁神话") ?? 0;
        $checkResultString = $diceOperations[2]->expression . "=" . $diceOperations[2]->rollResult;
        $decreaseResult = $checkSuccess ? $decreaseResults[0] : $decreaseResults[1];

        $characterCard->set("SAN", $afterSanity);
        $this->reply = Customization::getCustomReply("sanCheckResult", $this->userNickname,
            $checkResultString, $beforeSanity, Customization::getCustomReply("_sanCheckLevel")[$checkSuccess],
            $decreaseResult, $afterSanity, $maxSanity);
    }
}
