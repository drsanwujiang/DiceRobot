<?php /** @noinspection PhpUnusedPrivateFieldInspection */

declare(strict_types=1);

namespace DiceRobot\Enum;

use DiceRobot\Data\Report\Message;
use InvalidArgumentException;

/**
 * Class MessageTypeEnum
 *
 * Enum class. Message type enum.
 *
 * @package DiceRobot\Enum
 *
 * @method static MessageTypeEnum FRIEND()
 * @method static MessageTypeEnum GROUP()
 * @method static MessageTypeEnum TEMP()
 */
final class MessageTypeEnum extends Enum
{
    /** @var string Friend message. */
    private const FRIEND = "friend";

    /** @var string Group message. */
    private const GROUP = "group";

    /** @var string Temp message. */
    private const TEMP = "temp";

    /**
     * Parse message type.
     *
     * @param Message $message Message.
     *
     * @return MessageTypeEnum Message type.
     */
    public static function fromMessage(Message $message): MessageTypeEnum
    {
        if ($message instanceof Message\FriendMessage) {
            return self::FRIEND();
        } elseif ($message instanceof Message\GroupMessage) {
            return self::GROUP();
        } elseif ($message instanceof Message\TempMessage) {
            return self::TEMP();
        }

        throw new InvalidArgumentException();
    }
}
