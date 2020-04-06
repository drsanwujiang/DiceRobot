<?php
namespace DiceRobot\Service\API\Response;

use DiceRobot\Service\API\Response;

/**
 * Response of authorization.
 */
class AuthorizeResponse extends Response
{
    public string $token;

    protected function parse(): void
    {
        $this->token = $this->data["access_token"];
    }
}
