<?php

declare(strict_types=1);

use Co\Http\Server;
use DiceRobot\App;
use DiceRobot\Factory\{LoggerFactory, ServerFactory};
use DiceRobot\Service\{ApiService, ResourceService, RobotService};
use Psr\Container\ContainerInterface;
use Selective\Config\Configuration;

use const DiceRobot\DEFAULT_CONFIG;

return [
    App::class => function (
        ContainerInterface $container,
        Configuration $config,
        ApiService $api,
        ResourceService $resource,
        RobotService $robot,
        LoggerFactory $loggerFactory
    ): App {
        return new App($container, $config, $api, $resource, $robot, $loggerFactory);
    },

    Server::class => function (
        Configuration $config,
        App $app
    ): Server {
        return ServerFactory::create($config, $app);
    },

    Configuration::class => function (): Configuration {
        return new Configuration(
            array_replace_recursive(
                DEFAULT_CONFIG,
                require __DIR__ . "/settings.php"
            )
        );
    },

    LoggerFactory::class => function (
        Configuration $config
    ): LoggerFactory {
        return new LoggerFactory($config);
    }
];
