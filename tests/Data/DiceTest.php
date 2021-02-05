<?php

declare(strict_types=1);

namespace DiceRobot\Tests\Data;

use DiceRobot\Data\Config;
use DiceRobot\Data\Dice;
use DiceRobot\Data\Subexpression;
use DiceRobot\Exception\DiceException\DiceNumberOverstepException;
use DiceRobot\Exception\DiceException\ExpressionErrorException;
use DiceRobot\Exception\DiceException\ExpressionInvalidException;
use DiceRobot\Exception\DiceException\SurfaceNumberOverstepException;
use DiceRobot\Tests\TestCase;

class DiceTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $container = self::getContainer();

        $config = $container->get(Config::class);

        Dice::globalInitialize($config);
        Subexpression::globalInitialize($config);
    }

    public function provideOrder(): array
    {
        return [
                        /** Order                        vType  bpType  bpDiceN expression          reason           */
            "order1"    => ["d",                        [null,  null,   null,   "D100",             ""              ]],
            "order2"    => ["6D90",                     [null,  null,   null,   "6D90",             ""              ]],
            "order3"    => ["h",                        ["H",   null,   null,   "D100",             ""              ]],
            "order4"    => ["h (5D80K2+10)x5 Reason",   ["H",   null,   null,   "(5D80K2+10)*5",    "Reason"        ]],
            "order5"    => ["s",                        ["S",   null,   null,   "D100",             ""              ]],
            "order6"    => ["s (D60+5)*2 Reason",       ["S",   null,   null,   "(D60+5)*2",        "Reason"        ]],
            "order7"    => ["b",                        [null,  "B",    1,      "D100",             ""              ]],
            "order8"    => ["b3 Reason",                [null,  "B",    3,      "D100",             "Reason"        ]],
            "order9"    => ["p",                        [null,  "P",    1,      "D100",             ""              ]],
            "order10"   => ["p5 Reason",                [null,  "P",    5,      "D100",             "Reason"        ]],
            "order11"   => ["h (8DK3+10)x5 Reason",     ["H",   null,   null,   "(8D100K3+10)*5",   "Reason"        ]],
            "order12"   => ["d100",                     [null,  null,   null,   "D100",             ""              ]],
            "order13"   => ["dk",                       [null,  null,   null,   "D100K1",           ""              ]],
            "order14"   => ["kd",                       [null,  null,   null,   "D100",             "kd"            ]],
            "order15"   => ["dd",                       [null,  null,   null,   "D100",             "dd"            ]],
        ];
    }

    public function provideInvalidOrder(): array
    {
        return [
            "order1"    => ["4D100-"                        ],
            "order2"    => ["+"                         ],
        ];
    }

    /**
     * @param string $order
     * @param array $expected
     *
     * @throws ExpressionInvalidException
     * @throws DiceNumberOverstepException
     * @throws ExpressionErrorException
     * @throws SurfaceNumberOverstepException
     *
     * @dataProvider provideOrder
     */
    public function testOrder(string $order, array $expected): void
    {
        $dice = new Dice($order, -1);

        $this->assertEquals($expected[0], $dice->vType, "'vType' does not equal.");
        $this->assertEquals($expected[1], $dice->bpType, "'bpType' does not equal.");

        if (isset($dice->bpType)) {
            $this->assertEquals($expected[2], $dice->bpDiceNumber, "'bpDiceNumber' does not equal.");
        }

        $this->assertEquals($expected[3], $dice->expression, "'expression' does not equal.");
        $this->assertEquals($expected[4], $dice->reason, "'reason' does not equal.");
    }

    /**
     * @param string $order
     *
     * @throws DiceNumberOverstepException
     * @throws ExpressionErrorException
     * @throws ExpressionInvalidException
     * @throws SurfaceNumberOverstepException
     *
     * @dataProvider provideInvalidOrder
     */
    public function testInvalidOrder(string $order): void
    {
        $this->expectException(ExpressionInvalidException::class);

        new Dice($order, -1);
    }
}
