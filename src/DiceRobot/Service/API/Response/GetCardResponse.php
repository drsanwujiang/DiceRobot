<?php
namespace DiceRobot\Service\API\Response;

use DiceRobot\Exception\InformativeException\APIException\FormatInvalidException;
use DiceRobot\Exception\InformativeException\APIException\NotFoundException;
use DiceRobot\Exception\InformativeException\APIException\UnexpectedErrorException;
use DiceRobot\Service\API\Response;

/**
 * Response of getting character card.
 */
class GetCardResponse extends Response
{
    public int $id;
    public int $type;
    public array $attributes;
    public array $skills;

    protected function parse(): void
    {
        $this->id = $this->data["id"];
        $this->type = $this->data["type"];
        $this->attributes = $this->data["attributes"];
        $this->skills = $this->data["skills"];
    }

    /**
     * @throws FormatInvalidException
     * @throws NotFoundException
     * @throws UnexpectedErrorException
     */
    protected function validate(): void
    {
        if ($this->code == -1000)
            throw new NotFoundException();
        elseif ($this->code == -1001)
            throw new FormatInvalidException();
        elseif ($this->code != 0)
            $this->logError($this->code, $this->message);
    }
}
