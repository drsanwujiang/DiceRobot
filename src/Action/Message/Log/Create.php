<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\Log;

use DiceRobot\Action\Message\LogAction;
use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Data\Report\Message\TempMessage;
use DiceRobot\Data\Response\CreateLogResponse;
use DiceRobot\Enum\MessageTypeEnum;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Create
 *
 * Create a new TRPG log.
 *
 * @order log new
 *
 *      Sample: .log new
 *
 * @package DiceRobot\Action\Message\Log
 */
class Create extends LogAction
{
    /**
     * @inheritDoc
     *
     * @throws OrderErrorException
     */
    public function __invoke(): void
    {
        $this->parseOrder();

        if ($this->checkExists()) {
            $this->setReply("logExist");

            return;
        } elseif ($this->message instanceof TempMessage) {
            $this->setReply("logTempChatDenied");

            return;
        }

        $this->chatSettings->set("logUuid", $this->createLog()->uuid);
        $this->chatSettings->set("isLogging", true);

        $this->setReply("logCreate", [
            "机器人昵称" => $this->getRobotNickname()
        ]);
    }

    /**
     * Request to create a new TRPG log.
     *
     * @return CreateLogResponse The response.
     */
    protected function createLog(): CreateLogResponse
    {
        return $this->api->createLog(
            $this->message instanceof GroupMessage ? $this->message->sender->group->id : $this->message->sender->id,
            MessageTypeEnum::fromMessage($this->message)->getValue(),
            $this->api->getToken($this->robot->getId())->token
        );
    }
}
