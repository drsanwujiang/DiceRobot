<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report;

use DiceRobot\Data\Report\Fragment\Source;
use DiceRobot\Factory\FragmentFactory;
use DiceRobot\Interfaces\Fragment\ParsableFragment;
use DiceRobot\Interfaces\Report;

/**
 * Class Message
 *
 * DTO. Mirai message report.
 *
 * @package DiceRobot\Data\Report
 */
abstract class Message implements Report
{
    /** @var object[] Mirai message chain */
    public array $messageChain;

    /** @var string Serialized fragments */
    public string $message = "";

    /** @var Source Message source */
    public Source $source;

    /**
     * Return serialized fragments (aka raw message).
     *
     * @return string Serialized fragments
     */
    public function __toString()
    {
        return $this->message;
    }

    /**
     * Parse message chain to serialized fragments.
     *
     * @return bool Parse success
     */
    public function parseMessageChain(): bool
    {
        foreach ($this->messageChain as $fragmentData) {
            $fragment = FragmentFactory::create($fragmentData);

            if ($fragment instanceof Source) {
                $this->source = $fragment;

                continue;
            } elseif (!($fragment instanceof ParsableFragment)) {
                return false;
            }

            $this->message .= $fragment;
        }

        $this->message = trim($this->message);

        return true;
    }
}
