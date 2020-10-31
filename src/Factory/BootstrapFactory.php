<?php

declare(strict_types=1);

namespace DiceRobot\Factory;

use DI\ContainerBuilder;
use DiceRobot\{App, Server};
use Exception;
use Psr\Container\ContainerInterface;
use Selective\Config\Configuration;

use const DiceRobot\DEFAULT_CONFIG;

/**
 * Class BootstrapFactory
 *
 * The factory of DiceRobot application and HTTP server.
 *
 * @package DiceRobot\Factory
 */
class BootstrapFactory
{
    /** @var ContainerInterface Container */
    protected static ContainerInterface $container;

    /**
     * Create DiceRobot application.
     *
     * @return App Application
     *
     * @throws Exception
     */
    public static function createApp(): App
    {
        if (!isset(static::$container)) {
            $containerBuilder = new ContainerBuilder();
            $containerBuilder->addDefinitions([
                Configuration::class => function (): Configuration {
                    return new Configuration(DEFAULT_CONFIG);
                }
            ]);
            static::$container = $containerBuilder->build();
        }

        return static::$container->get(App::class);
    }

    /**
     * Create DiceRobot HTTP server.
     *
     * @return Server Server
     *
     * @throws Exception
     */
    public static function createServer(): Server
    {
        if (!isset(static::$container)) {
            $containerBuilder = new ContainerBuilder();
            $containerBuilder->addDefinitions([
                Configuration::class => function (): Configuration {
                    return new Configuration(DEFAULT_CONFIG);
                }
            ]);
            static::$container = $containerBuilder->build();
        }

        return static::$container->get(Server::class);
    }

    /**
     * Set container.
     *
     * @param ContainerInterface $container Container
     */
    public static function setContainer(ContainerInterface $container): void
    {
        static::$container = $container;
    }
}
