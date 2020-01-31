<?php

namespace Afk11\AdsbxProxy;

class ApiClient {
    private \React\HttpClient\Client $client;
    private array $headers = [];
    public function __construct(\React\HttpClient\Client $client, string $apiKey)
    {
        $this->client = $client;
        $this->headers = ['api-auth' => $apiKey];
    }
    public function getRawData(): \React\Promise\PromiseInterface {
        $request = $this->client->request('GET', 'https://adsbexchange.com/api/aircraft/json/', $this->headers);

        $deferred = new \React\Promise\Deferred();
        $request->on('response', function ($response) use ($deferred) {
            $buffer = '';
            $response->on('data', function ($chunk) use ($deferred, &$buffer) {
                $buffer .= $chunk;
            });
            $response->on('end', function () use ($deferred, &$buffer) {
                $deferred->resolve($buffer);
            });
        });
        $request->on('error', function (\Exception $e) use ($deferred) {
            $deferred->reject($e);
        });

        $request->end();
        return $deferred->promise();
    }
    public function getData(): \React\Promise\PromiseInterface {
        return $this
            ->getRawData()
            ->then(function (string $jsonStr) {
                return json_decode($jsonStr);
            });
    }
}
