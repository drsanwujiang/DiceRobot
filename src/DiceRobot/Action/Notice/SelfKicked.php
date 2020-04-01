<?php
namespace DiceRobot\Action\Notice;

use DiceRobot\Action\Action;
use DiceRobot\Exception\CredentialException;
use DiceRobot\Service\APIService;

/**
 * Submit group ID when kicked out of a group.
 */
final class SelfKicked extends Action
{
    /**
     * @throws CredentialException
     */
    public function __invoke(): void
    {
        APIService::submitDelinquentGroup($this->groupId, $this->getCredential()); // Submit this group to public database
        $this->noResponse();
    }

    /**
     * Request API credential.
     *
     * @return string Credential
     *
     * @throws CredentialException
     */
    private function getCredential(): string
    {
        $response = APIService::getAPICredential($this->selfId);

        if ($response["code"] != 0)
        {
            $errMessage = "DiceRobot submits delinquent group failed: " . $response["message"] . "\n" .
                "Delinquent group ID: " . $this->groupId;

            throw new CredentialException($errMessage);
        }

        return $response["data"]["credential"];
    }
}
