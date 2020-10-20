<?php

declare(strict_types=1);

namespace DiceRobot\Factory;

use DiceRobot\Data\Report\Fragment\{App, At, AtAll, Face, FlashImage,Image, Json, Plain, Poke, Quote, Source,
    UnknownFragment, Voice, Xml};
use DiceRobot\Interfaces\Fragment;
use DiceRobot\Interfaces\Fragment\ParsableFragment;
use DiceRobot\Util\Convertor;

/**
 * Class FragmentFactory
 *
 * The factory of Mirai message chain fragment (aka single message).
 *
 * @package DiceRobot\Factory
 */
class FragmentFactory
{
    /** @var string[] Fragment mapping */
    protected const FRAGMENT_MAPPING = [
        "App" => App::class,
        "At" => At::class,
        "AtAll" => AtAll::class,
        "Face" => Face::class,
        "FlashImage" => FlashImage::class,
        "Image" => Image::class,
        "Json" => Json::class,
        "Plain" => Plain::class,
        "Poke" => Poke::class,
        "Quote" => Quote::class,
        "Source" => Source::class,
        "Voice" => Voice::class,
        "Xml" => Xml::class,

        "UnknownFragment" => UnknownFragment::class
    ];

    /** @var string[] Parsable fragment mapping */
    protected const PARSABLE_FRAGMENT_MAPPING = [
        "at" => At::class,
        "atall" => AtAll::class,
        "face" => Face::class,
        "image" => Image::class,

        "plain" => Plain::class,
    ];

    /**
     * Create fragment from JSON parsed object.
     *
     * @param object $fragmentData JSON parsed object
     *
     * @return Fragment The fragment
     */
    public static function create(object $fragmentData): Fragment
    {
        $type = $fragmentData->type ?? "UnknownFragment";
        $class = static::FRAGMENT_MAPPING[$type] ?? static::FRAGMENT_MAPPING["UnknownFragment"];

        return Convertor::toCustomInstance($fragmentData, $class, static::FRAGMENT_MAPPING["UnknownFragment"]);
    }

    /**
     * Create parsable fragment from Mirai code.
     *
     * @param string $code Mirai code
     *
     * @return ParsableFragment The parsable fragment
     */
    public static function fromMiraiCode(string $code): ParsableFragment
    {
        if (preg_match("/^\[mirai:(\w+)(?::.+?)?]$/i", $code, $matches) &&
            array_key_exists($matches[1], static::PARSABLE_FRAGMENT_MAPPING)
        ) {
            $fragmentType = static::PARSABLE_FRAGMENT_MAPPING[$matches[1]];

            /** @var ParsableFragment $fragment */
            $fragment = new $fragmentType();
            $fragment->fromMiraiCode($code);
        }
        else
        {
            $fragment = new Plain();
            $fragment->fromMiraiCode($code);
        }

        return $fragment;
    }
}
