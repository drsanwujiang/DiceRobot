<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\Response;
use DiceRobot\Exception\CharacterCardException\{FormatInvalidException, NotFoundException};

/**
 * Class GetCardResponse
 *
 * DTO. Response of getting character card.
 *
 * @package DiceRobot\Data\Response
 */
final class GetCardResponse extends Response
{
    /** @var int Character card ID */
    public int $id;

    /** @var int Character card type */
    public int $type;

    /** @var array Investigator's attributes */
    public array $attributes;

    /** @var array Investigator's attributes */
    public array $skills;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->id = (int) $this->data["id"];
        $this->type = (int) $this->data["type"];
        $this->attributes = (array) $this->data["attributes"];
        $this->skills = (array) $this->data["skills"];
    }

    /**
     * @inheritDoc
     *
     * @throws FormatInvalidException
     * @throws NotFoundException
     */
    protected function validate(): void
    {
        if ($this->code == -1000) {
            throw new NotFoundException();
        }
        elseif ($this->code == -1001) {
            throw new FormatInvalidException();
        }
    }
}
