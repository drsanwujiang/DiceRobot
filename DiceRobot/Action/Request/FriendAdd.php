<?php
namespace DiceRobot\Action\Request;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\API;

/**
 * Process the add friend request.
 */
final class FriendAdd extends AbstractAction
{
    public function __invoke(): void
    {
        $approve = true;  // Default permit request

        API::setFriendAddRequestAsync($this->flag, $approve);
        $this->noResponse();
    }
}
