<?php
/**
 * DiceRobot 1.2.0
 * ©2019-2020 Drsanwujiang
 *
 * A TRPG dice robot based on CoolQ HTTP API plugin.
 */

use DiceRobot\App;

require "autoloader.php";
require "settings.php";
require "customreply.php";

// Collect event data
$eventData = json_decode(file_get_contents("php://input"));

$app = new App($eventData);

// Register routes
(require "routes.php")($app);

$app->run();

http_response_code($app->getHttpCode());

if ($app->getHttpCode() == 200)
    echo(json_encode(["reply" => $app->getReply(), "at_sender" => $app->getAtSender(), "block" => $app->getBlock()]));
