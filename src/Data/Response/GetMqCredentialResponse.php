<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\DiceRobotResponse;

/**
 * Class GetMqCredentialResponse
 *
 * DTO. Response of getting message queue credential.
 *
 * @package DiceRobot\Data\Response
 */
final class GetMqCredentialResponse extends DiceRobotResponse
{
    /** @var string $clientId Client ID. */
    public string $clientId;

    /** @var string $credentialId Client credential ID. */
    public string $credentialId;

    /** @var string $credentialSecret Client credential secret. */
    public string $credentialSecret;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->clientId = (string) $this->data["client_id"];
        $this->credentialId = (string) $this->data["credential_id"];
        $this->credentialSecret = (string) $this->data["credential_secret"];
    }
}
