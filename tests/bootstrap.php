<?php

use DiceRobot\{App, Server};
use DI\Container;

require_once __DIR__ . "/../vendor/autoload.php";

Co\run(function () {
    $container = new Container();
    $app = $container->get(App::class);
    $server = $container->get(Server::class);

    $app->initialize();
    $server->start();
});
