<?php
namespace DiceRobot\Service\API\Response;

use DiceRobot\Exception\InformativeException\APIException\PermissionDeniedException;
use DiceRobot\Exception\InformativeException\APIException\UnexpectedErrorException;
use DiceRobot\Service\API\Response;

/**
 * Response of updating character card.
 */
class UpdateCardResponse extends Response
{
    public int $afterValue;

    protected function parse(): void
    {
        $this->afterValue = $this->data["after_value"];
    }

    /**
     * @throws PermissionDeniedException
     * @throws UnexpectedErrorException
     */
    protected function validate(): void
    {
        if ($this->code == -1012)
            throw new PermissionDeniedException();
        elseif ($this->code != 0)
            $this->logError($this->code, $this->message);
    }
}
