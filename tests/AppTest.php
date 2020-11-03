<?php

declare(strict_types=1);

namespace DiceRobot\Tests;

use DI\Container;
use DiceRobot\App;
use DiceRobot\Enum\AppStatusEnum;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    public function testInitialize(): void
    {
        $container = new Container();
        $app = $container->get(App::class);

        $app->initialize();

        $this->assertEquals(AppStatusEnum::HOLDING(), $app->getStatus());

        $app->stop();
    }
}
