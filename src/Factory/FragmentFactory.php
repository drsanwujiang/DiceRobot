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
    /** @var string[] Mapping between fragment and the full name of the corresponding class. */
    protected const FRAGMENTS = [
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

    /** @var string[] Mapping between parsable fragment and the full name of the corresponding class. */
    protected const PARSABLE_FRAGMENTS = [
        "at" => At::class,
        "atall" => AtAll::class,
        "face" => Face::class,
        "image" => Image::class,

        "plain" => Plain::class,
    ];

    /**
     * Create fragment from parsed JSON object.
     *
     * @param object $data Fragment data (parsed JSON object).
     *
     * @return Fragment The fragment.
     */
    public static function create(object $data): Fragment
    {
        $type = $data->type ?? "UnknownFragment";
        $class = static::FRAGMENTS[$type] ?? static::FRAGMENTS["UnknownFragment"];

        return Convertor::toCustomInstance($data, $class, static::FRAGMENTS["UnknownFragment"]);
    }

    /**
     * Create parsable fragment from Mirai code.
     *
     * @param string $code Mirai code.
     *
     * @return ParsableFragment The parsable fragment.
     */
    public static function fromMiraiCode(string $code): ParsableFragment
    {
        if (preg_match("/^\[mirai:(\w+)(?::.+?)?]$/i", $code, $matches) &&
            array_key_exists($matches[1], static::PARSABLE_FRAGMENTS)
        ) {
            $fragmentType = static::PARSABLE_FRAGMENTS[$matches[1]];

            /** @var ParsableFragment $fragment */
            $fragment = new $fragmentType();
            $fragment->fromMiraiCode($code);
        } else {
            $fragment = new Plain();
            $fragment->fromMiraiCode($code);
        }

        return $fragment;
    }
}
