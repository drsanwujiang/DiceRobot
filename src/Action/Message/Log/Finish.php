<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\Log;

use DiceRobot\Action\Message\LogAction;
use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Data\Response\FinishLogResponse;
use DiceRobot\Enum\MessageTypeEnum;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Finish
 *
 * Finish the TRPG log.
 *
 * @order log end
 *
 *      Sample: .log end
 *
 * @package DiceRobot\Action\Message\Log
 */
class Finish extends LogAction
{
    /**
     * @inheritDoc
     *
     * @throws OrderErrorException
     */
    public function __invoke(): void
    {
        $this->parseOrder();

        if (!$this->checkExists()) {
            $this->setReply("logNotExist");

            return;
        }

        $uuid = $this->chatSettings->getString("logUuid");

        // Clear UUID and log state first
        $this->chatSettings->set("logUuid", "");
        $this->chatSettings->set("isLogging", false);

        // Then send request
        $this->setReply("logFinish", [
            "Log地址" => $this->finishLog($uuid)->url
        ]);
    }

    /**
     * Request to finish the TRPG log.
     *
     * @param string $uuid Log UUID.
     *
     * @return FinishLogResponse The response.
     */
    protected function finishLog(string $uuid): FinishLogResponse
    {
        return $this->api->finishLog(
            $this->message instanceof GroupMessage ? $this->message->sender->group->id : $this->message->sender->id,
            MessageTypeEnum::fromMessage($this->message)->getValue(),
            $uuid,
            $this->api->getToken($this->robot->getId())->token
        );
    }
}
