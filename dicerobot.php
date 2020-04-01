<?php
/**
 * DiceRobot 1.3.0
 * ©2019-2020 Drsanwujiang
 *
 * A TRPG dice robot based on CoolQ HTTP APIService plugin.
 */

use DiceRobot\App;

// Register autoloader
require __DIR__ . "/autoloader.php";

// Set up settings
require __DIR__ . "/config.php";

// Collect event data
$eventData = json_decode(file_get_contents("php://input"));

// Create App instance
$app = new App($eventData);

// Register routes
(require __DIR__ . "/routes.php")($app);

$app->run();
