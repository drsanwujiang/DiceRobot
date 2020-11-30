<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment;

/**
 * Class Quote
 *
 * DTO. Quote message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class Quote implements Fragment
{
    /** @var int Origin message ID. */
    public int $id;

    /** @var int Origin group ID. */
    public int $groupId;

    /** @var int Origin sender ID. */
    public int $senderId;

    /** @var int Origin receiver ID. */
    public int $targetId;

    /** @var object[] Origin message chain. */
    public array $origin;

    /**
     * @inheritDoc
     *
     * @return array Message.
     */
    public function toMessage(): array
    {
        return [
            "type" => "Quote",
            "id" => $this->id,
            "groupId" => $this->groupId,
            "senderId" => $this->senderId,
            "targetId" => $this->targetId,
            "origin" => $this->origin
        ];
    }
}
