<?php

declare(strict_types=1);

namespace DiceRobot\Tests;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected static function getContainer(): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(__DIR__ . "/skeleton/config/container.php");

        return $containerBuilder->build();
    }
}
