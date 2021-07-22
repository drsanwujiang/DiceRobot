<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

/**
 * Class MessageNode
 *
 * DTO. Message node.
 *
 * MessageNode is part of Forward message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class MessageNode
{
    /** @var int|null Message sender ID. */
    public ?int $senderId;

    /** @var int|null Message send time. */
    public ?int $time;

    /** @var string|null Message sender nickname. */
    public ?string $senderName;

    /** @var object[]|null Mirai message chain. */
    public ?array $messageChain;

    /** @var int|null Message ID. */
    public ?int $messageId;
}
