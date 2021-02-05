<?php

declare(strict_types=1);

namespace DiceRobot\Util;

use DiceRobot\Data\Report\Fragment\Image;
use DiceRobot\Data\Report\Fragment\Plain;

/**
 * Class MessageSplitter
 *
 * Util class. Message splitter.
 *
 * @package DiceRobot\Util
 */
class MessageSplitter
{
    /**
     * Split the messages to avoid long message (especially in private chat).
     *
     * @param string[] $messages messages.
     * @param int $maxCharacter Maximum number of character.
     *
     * @return array Split messages.
     */
    public static function split(array $messages, int $maxCharacter): array
    {
        $splitMessages = [];

        foreach ($messages as $_message) {
            $message = "";
            $messageLength = 0;

            $parsableFragments = Convertor::toFragments($_message);

            foreach ($parsableFragments as $parsableFragment) {
                $fragment = (string) $parsableFragment;
                /*
                 * It's assumed that an image is 200 characters long, which doesn't make a lot of sense, but maybe
                 * useful for private long message.
                 *
                 * Other parsable fragments like Face, At and AtAll will be treated as long as its Mirai code.
                 */
                $fragmentLength = $fragment instanceof Image ? 200 : mb_strlen($fragment);

                if ($parsableFragment instanceof Plain && $messageLength + $fragmentLength > $maxCharacter) {
                    // If the fragment is an overlength plain text, split it into two parts
                    $splitMessages[] = $message . mb_substr($fragment, 0, $maxCharacter - $messageLength);
                    $fragment = mb_substr($fragment, $maxCharacter - $messageLength);
                    $fragmentLength = mb_strlen($fragment);
                    $message = "";
                    $messageLength = 0;
                } elseif ($messageLength + $fragmentLength > $maxCharacter) {
                    // If the fragment is overlength, split the message
                    $splitMessages[] = $message;
                    $message = "";
                    $messageLength = 0;
                }

                $message .= $fragment;
                $messageLength += $fragmentLength;
            }

            $splitMessages[] = $message;
        }

        return $splitMessages;
    }
}
