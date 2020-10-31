<?php

use DiceRobot\Factory\BootstrapFactory;

require_once __DIR__ . "/../vendor/autoload.php";

Co\run(function () {
    // Create App instance
    $app = BootstrapFactory::createApp();

    // Create Server instance
    $server = BootstrapFactory::createServer();

    // Initialize application
    $app->initialize();

    // Start event loop
    $server->start();
});
