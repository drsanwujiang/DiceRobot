<?php
namespace DiceRobot\Action\Request;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;

/**
 * Process the friend adding request.
 */
final class FriendAdd extends Action
{
    /**
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function __invoke(): void
    {
        $this->coolq->setFriendAddRequestAsync($this->flag, true);  // Approve request by default
        $this->noResponse();
    }
}
