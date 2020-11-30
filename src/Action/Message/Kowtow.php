<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Exception\OrderErrorException;
use DiceRobot\Util\Convertor;

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

        $piety = $this->api->kowtow($this->message->sender->id)->piety;
        $level = $this->getKowtowLevel($piety);

        $this->setReply("kowtowResult", [
            "发送者QQ" => $this->message->sender->id,
            "机器人昵称" => $this->getRobotNickname(),
            "虔诚值" => $piety,
            "虔诚等级" => Convertor::toCustomString($this->config->getReply("kowtowLevel{$level}"), [
                "机器人昵称" => $this->getRobotNickname()
            ])
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
