<?php

// Just start this server and connect to it. Everything you send to it will be
// sent back to you.
//
// $ php examples/01-echo-server.php 8000
// $ telnet localhost 8000
//
// You can also run a secure TLS echo server like this:
//
// $ php examples/01-echo-server.php tls://127.0.0.1:8000 examples/localhost.pem
// $ openssl s_client -connect localhost:8000
//
// You can also run a Unix domain socket (UDS) server like this:
//
// $ php examples/01-echo-server.php unix:///tmp/server.sock
// $ nc -U /tmp/server.sock

use React\EventLoop\Factory;
use React\Socket\Server;
use React\Socket\ConnectionInterface;

require __DIR__ . '/vendor/autoload.php';

$loop = Factory::create();

$server = new Server(isset($argv[1]) ? $argv[1] : 0, $loop, array(
));

$deferred = new \React\Promise\Deferred();
$deferred->promise()
    ->then(function (ConnectionInterface $connection) use ($loop) {
        $nextState = new \React\Promise\Deferred();
        $doMessages = $loop->addPeriodicTimer(1, function () use ($connection) {
            $connection->write("MSG,3,0,0,4CA336,0,2020/02/01,12:18:23.000,2020/02/01,12:18:23.000,,8400,,,53.450317,-8.01413,,,,,,\r\n");
        });
        $loop->addTimer(30, function () use ($loop, $doMessages, $nextState, $connection) {
            $loop->cancelTimer($doMessages);
            $nextState->resolve($connection);
        });
        return $nextState->promise();
    })
    ->then(function (ConnectionInterface $connection) use ($loop) {
        $nextState = new \React\Promise\Deferred();
        $loop->addTimer(70, function () use ($loop, $nextState) {
            $nextState->resolve();
        });
        return $nextState->promise();
    })
    ->then(function (ConnectionInterface $connection) use ($loop) {
        $nextState = new \React\Promise\Deferred();
        $doMessages = $loop->addPeriodicTimer(1, function () use ($connection) {
            $connection->write("MSG,3,0,0,4CA336,0,2020/02/01,12:18:23.000,2020/02/01,12:18:23.000,,8400,,,53.450317,-8.01413,,,,,,\r\n");
        });
        $loop->addTimer(1*60, function () use ($loop, $doMessages, $nextState) {
            $loop->cancelTimer($doMessages);
            $nextState->resolve();
        });
        return $nextState->promise();
    });
$server->on('connection', function (ConnectionInterface $connection) use ($deferred) {
    echo '[' . $connection->getRemoteAddress() . ' connected]' . PHP_EOL;
    $deferred->resolve($connection);
});

$server->on('error', 'printf');

echo 'Listening on ' . $server->getAddress() . PHP_EOL;

$loop->run();