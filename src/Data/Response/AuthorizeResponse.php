<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\DiceRobotResponse;

/**
 * Class AuthorizeResponse
 *
 * DTO. Response of authorization.
 *
 * @package DiceRobot\Data\Response
 */
final class AuthorizeResponse extends DiceRobotResponse
{
    /** @var string JWT token. */
    public string $token;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->token = (string) $this->data["access_token"];
    }
}
