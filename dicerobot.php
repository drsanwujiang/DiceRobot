<?php
/**
 * DiceRobot 1.4.0
 * ©2019-2020 Drsanwujiang
 *
 * A TRPG dice robot based on OneBot standard plugin.
 */

use DiceRobot\App;

// Register autoloader
require __DIR__ . "/autoloader.php";

// Set up settings
require __DIR__ . "/config.php";

// Create App instance
$app = new App();

// Register routes
(require __DIR__ . "/routes.php")($app);

// Execute main logic
$app->run();
