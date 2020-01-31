<?php

namespace Afk11\AdsbxProxy;

use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;
use React\Http\Server;

class Application
{
    public function run() {
        $refreshSeconds = getenv("ADSBX_CACHE_TIME") ?: 5;
        $loop = \React\EventLoop\Factory::create();
        $httpClient = new \React\HttpClient\Client($loop);
        $apiKey = getenv("ADSBX_API_KEY");
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

        $socket = new \React\Socket\Server(8080, $loop);
        $server->listen($socket);
        $loop->run();
    }
}
