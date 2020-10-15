<?php

declare(strict_types=1);

namespace DiceRobot\Exception;

use Exception;

/**
 * Class MiraiApiException
 *
 * This exception indicates that DiceRobot cannot connect Mirai API HTTP server, or Mirai API HTTP server returned
 * unexpected HTTP status code, for all the response from Mirai API HTTP server (regardless of whether the request is
 * correct) should return HTTP status code 200.
 *
 * @package DiceRobot\Exception
 */
class MiraiApiException extends Exception
{}
