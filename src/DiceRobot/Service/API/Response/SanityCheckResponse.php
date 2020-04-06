<?php
namespace DiceRobot\Service\API\Response;

use DiceRobot\Exception\InformativeException\APIException\PermissionDeniedException;
use DiceRobot\Exception\InformativeException\APIException\UnexpectedErrorException;
use DiceRobot\Service\API\Response;

/**
 * Response of sanity check.
 */
class SanityCheckResponse extends Response
{
    public bool $checkSuccess;
    public int $beforeSanity;
    public int $afterSanity;

    protected function parse(): void
    {
        $this->checkSuccess = $this->data["check_success"];
        $this->beforeSanity = $this->data["before_sanity"];
        $this->afterSanity = $this->data["after_sanity"];
    }

    /**
     * @throws PermissionDeniedException
     * @throws UnexpectedErrorException
     */
    protected function validate(): void
    {
        if ($this->code == -1024)
            throw new PermissionDeniedException();
        elseif ($this->code != 0)
            $this->logError($this->code, $this->message);
    }
}
