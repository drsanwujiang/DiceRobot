<?php

declare(strict_types=1);

namespace DiceRobot\Tests;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function getContainer(): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(__DIR__ . "/config/container.php");

        return $containerBuilder->build();
    }
}
