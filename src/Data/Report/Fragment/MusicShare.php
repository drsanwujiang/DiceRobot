<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment;

/**
 * Class MusicShare
 *
 * DTO. Music share message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class MusicShare implements Fragment
{
    /** @var string Music share kind. */
    public string $kind;

    /** @var string Music share title. */
    public string $title;

    /** @var string Music share summary. */
    public string $summary;

    /** @var string Music share jump URL. */
    public string $jumpUrl;

    /** @var string Music share picture URL. */
    public string $pictureUrl;

    /** @var string Music URL. */
    public string $musicUrl;

    /** @var string Music share brief. */
    public string $brief;

    /**
     * @inheritDoc
     *
     * @return array Message.
     */
    public function toMessage(): array
    {
        return [
            "type" => "MusicShare",
            "kind" => $this->kind,
            "title" => $this->title,
            "summary" => $this->summary,
            "jumpUrl" => $this->jumpUrl,
            "pictureUrl" => $this->pictureUrl,
            "musicUrl" => $this->musicUrl,
            "brief" => $this->brief,
        ];
    }
}
