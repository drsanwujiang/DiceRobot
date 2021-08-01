<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Response\GetPietyResponse;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Kowtow
 *
 * Kowtow to the robot, and it will show your piety~
 *
 * @order orz
 *
 *      Sample: .orz
 *
 * @package DiceRobot\Action\Message
 */
class Kowtow extends MessageAction
{
    /** @var int[] Kowtow levels. */
    protected const KOWTOW_LEVEL = [10, 30, 60, 80, 95, 100];

    /**
     * @inheritDoc
     *
     * @throws OrderErrorException
     */
    public function __invoke(): void
    {
        if (!$this->checkEnabled()) {
            return;
        }

        $this->parseOrder();

        $piety = $this->getPiety()->piety;
        $level = $this->getKowtowLevel($piety);

        $this->setReply("kowtowResult", [
            "虔诚值" => $piety,
            "虔诚等级" => $this->getCustomReply("kowtowLevel{$level}", ["机器人昵称" => $this->getRobotNickname()])
        ]);

    }

    /**
     * @inheritDoc
     *
     * @return bool Enabled.
     */
    protected function checkEnabled(): bool
    {
        if (!$this->config->getStrategy("enableKowtow")) {
            $this->setReply("kowtowDisabled");

            return false;
        } else {
            return true;
        }
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements.
     *
     * @throws OrderErrorException Order is invalid.
     */
    protected function parseOrder(): array
    {
        if (!preg_match("/^$/", $this->order)) {
            throw new OrderErrorException;
        }

        return [];
    }

    /**
     * Request to get piety.
     *
     * @return GetPietyResponse The response.
     */
    protected function getPiety(): GetPietyResponse
    {
        return $this->api->getPiety(
            $this->message->sender->id,
            $this->api->getToken($this->robot->getId())->token
        );
    }

    /**
     * Get kowtow level.
     *
     * @param int $piety Piety.
     *
     * @return int|null Level.
     */
    protected function getKowtowLevel(int $piety): ?int
    {
        for ($level = 0; $level < count(self::KOWTOW_LEVEL); $level++) {
            if ($piety <= self::KOWTOW_LEVEL[$level]) {
                return $level;
            }
        }

        return null;
    }
}
