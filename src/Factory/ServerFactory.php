<?php /** @noinspection PhpUnusedParameterInspection */

declare(strict_types=1);

namespace DiceRobot\Factory;

use Co\Http\Server;
use DiceRobot\App;
use Selective\Config\Configuration;
use Swoole\Http\{Request, Response};

/**
 * Class ServerFactory
 *
 * The factory of Swoole HTTP server.
 *
 * @package DiceRobot\Factory
 */
class ServerFactory
{
    /**
     * Create Swoole HTTP server.
     *
     * @param Configuration $config The config
     * @param App $app DiceRobot application
     *
     * @return Server The Swoole HTTP server
     */
    public static function create(Configuration $config, App $app): Server
    {
        $host = $config->getString("dicerobot.server.host");
        $port = $config->getInt("dicerobot.server.port");

        // Create Swoole HTTP server instance
        $server = new Server($host, $port);

        // Route "/report", handling message and event report from Mirai API HTTP plugin
        $server->handle('/report', function (Request $request, Response $response) use($app) {
            $app->report($request->getContent());
            $response->status(204);  // Respond nothing to report service
            $response->end();
        });

        // Route "/heartbeat", handling heartbeat from Mirai API HTTP plugin
        $server->handle('/heartbeat', function (Request $request, Response $response) use($app) {
            $app->heartbeat();
            $response->status(204);  // Respond nothing to heartbeat service
            $response->end();
        });

        $server->handle('/pause', function (Request $request, Response $response) use ($server) {
            $response->end("<h1>Paused!</h1>");
        });

        $server->handle('/continue', function (Request $request, Response $response) use ($server) {
            $response->end("<h1>Continued!</h1>");
        });

        $server->handle('/reload', function (Request $request, Response $response) use ($server) {
            $response->end("<h1>Reloaded!</h1>");
        });

        $server->handle('/stop', function (Request $request, Response $response) use ($app, $server) {
            $app->stop();
            $response->end("<h1>Stopped!</h1>");
            $server->shutdown();
        });

        return $server;
    }
}
