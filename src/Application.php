<?php

namespace Afk11\AdsbxProxy;

use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;
use React\Http\Server;

class Application
{
    public function run() {
        $refreshSeconds = getenv("ADSBX_CACHE_TIME") ?: 5;
        $bindHost = getenv("ADSBX_BIND_HOST") ?: "127.0.0.1";
        $bindPort = (int) (getenv("ADSBX_BIND_PORT") ?: 8080);
        $apiKey = getenv("ADSBX_API_KEY");

        $loop = \React\EventLoop\Factory::create();
        $httpClient = new \React\HttpClient\Client($loop);
        $adsbx = new ApiClient($httpClient, $apiKey);

        $cached = '';
        $loop->addPeriodicTimer($refreshSeconds, function () use ($adsbx, &$cached) {
            $adsbx->getRawData()->then(function (string $data) use (&$cached) {
                $cached = $data;
            });
        });

        $server = new Server(
            function (ServerRequestInterface $request) use (&$cached) {
                switch ($request->getUri()->getPath()) {
                    case "/api/aircraft/json/":
                        return new Response(
                            200,
                            array(
                                'Content-Type' => 'application/json'
                            ),
                            $cached
                        );
                    default:
                        return new Response(404);
                }

            });

        $socket = new \React\Socket\Server("$bindHost:$bindPort", $loop);
        $server->listen($socket);
        $loop->run();
    }
}
