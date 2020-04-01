<?php
namespace DiceRobot\Action\Request;

use DiceRobot\Action\Action;
use DiceRobot\Service\APIService;

/**
 * Process the add friend request.
 */
final class FriendAdd extends Action
{
    public function __invoke(): void
    {
        APIService::setFriendAddRequestAsync($this->flag, true);  // Default approve request
        $this->noResponse();
    }
}
