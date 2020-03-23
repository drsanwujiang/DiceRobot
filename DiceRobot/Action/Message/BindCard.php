<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\API;
use DiceRobot\Base\CharacterCard;
use DiceRobot\Base\Customization;
use DiceRobot\Base\RobotSettings;
use DiceRobot\Exception\OrderErrorException;

/**
 * Bind/Unbind COC character card.
 */
final class BindCard extends AbstractAction
{
    public function __invoke(): void
    {
        $order = preg_replace("/^\.card[\s]*/i", "", $this->message, 1);

        if ($order == "")
        {
            RobotSettings::setCharacterCard($this->userId, NULL);
            $this->reply = Customization::getCustomReply("bindCardUnbind");
            return;
        }
        elseif (!preg_match("/^[1-9][0-9]*$/", $order))
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new OrderErrorException;

        // Send message
        $message = Customization::getCustomReply("bindCardPending");

        if ($this->chatType == "group")
            API::sendGroupMessageAsync($this->chatId, $message);
        elseif ($this->chatType == "discuss")
            API::sendDiscussMessageAsync($this->chatId, $message);
        elseif ($this->chatType == "private")
            API::sendPrivateMessageAsync($this->chatId, $message);

        $cardId = intval($order);
        $result = API::getAPICredential($this->selfId);  // Get credential

        if ($result["code"] != 0)
        {
            error_log("DiceRobot bind character card failed: " . $result["message"] . "\n" .
                "Delinquent group ID: " . $this->groupId);
            $this->noResponse();
            return;
        }

        // Request to get character card
        $result = API::getCharacterCard($this->userId, $cardId, $result["data"]["credential"]);

        if ($result["code"] == -3)
        {
            $this->reply = Customization::getCustomReply("bindCardPermissionDenied");
            return;
        }
        elseif ($result["code"] != 0)
        {
            $this->reply = Customization::getCustomReply("bindCardInternalError");
            return;
        }

        $characterCard = new CharacterCard($cardId);

        if (!$characterCard->parse($result["data"]["attributes"], $result["data"]["status"], $result["data"]["skills"]))
        {
            $this->reply = Customization::getCustomReply("bindCardFormatError");
            return;
        }

        $characterCard->save();
        RobotSettings::setCharacterCard($this->userId, $cardId);

        $this->reply = Customization::getCustomReply("bindCardSuccess");
    }
}
