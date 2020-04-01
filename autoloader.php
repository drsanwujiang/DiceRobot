<?php
/**
 * Class loader.
 */

spl_autoload_register(function ($class) {
    $dir = __DIR__ . "/src/";
    $file = $dir . str_replace("\\", "/", $class) . ".php";

    require $file;
});
