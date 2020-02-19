<?php
/**
 * DiceRobot 1.1.2
 * ©2019-2020 Drsanwujiang
 *
 * A TRPG dice robot based on CoolQ HTTP API plugin.
 */

require("autoloader.php");
require("settings.php");
require("customreply.php");

use DiceRobot\App;

// Main entrance
$eventData = json_decode(file_get_contents("php://input"));

$app = new App($eventData);
$app->addRoutes();
$app->run();

http_response_code($app->getHttpCode());

if ($app->getHttpCode() == 200)
{
    echo(json_encode(array("reply" => $app->getReply(), "at_sender" => $app->getAtSender(),
        "block" => $app->getBlock())));
}
