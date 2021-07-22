<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment;

/**
 * Class File
 *
 * DTO. File message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class File implements Fragment
{
    /** @var string File ID. */
    public string $id;

    /** @var string File name. */
    public string $name;

    /** @var int File size. */
    public int $size;

    /**
     * @inheritDoc
     *
     * @return array Message.
     */
    public function toMessage(): array
    {
        return [
            "type" => "File",
            "id" => $this->id,
            "name" => $this->name,
            "size" => $this->size
        ];
    }
}
