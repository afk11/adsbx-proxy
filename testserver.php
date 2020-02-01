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
        echo "fire initial messages\n";
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
        echo "wait 70 seconds\n";
        $nextState = new \React\Promise\Deferred();
        $loop->addTimer(70, function () use ($loop, $nextState, $connection) {
            $nextState->resolve($connection);
        });
        return $nextState->promise();
    })
    ->then(function (ConnectionInterface $connection) use ($loop) {
        echo "resume messages\n";
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
/*
 $ /var/lib/tracker/sbs1-tracker/artisan sbs1:run oxo  --host=localhost --port 12300 --track-squawks --track-transmission-types --track-callsigns --track-kml-whitelist=4CA13E,4CA13F,4CA023,4CA158,4CA1E7,4CA1E8,4CA1E9,4CA1EA,4CA1EB,4CA1EC,4CA1ED,4CA1EE,4CA1EF,4CA1F0,4CA204,4CA28B,4CA28C,4CA31A,4CA31E,4CA330,4CA331,4CA332,4CA335,4CA336,4CA41A,4CA98B,4CA98C,4CA98D,4CA98E,4CA98F  --debug-aircraft=4CA13E,4CA13F,4CA023,4CA158,4CA1E7,4CA1E8,4CA1E9,4CA1EA,4CA1EB,4CA1EC,4CA1ED,4CA1EE,4CA1EF,4CA1F0,4CA204,4CA28B,4CA28C,4CA31A,4CA31E,4CA330,4CA331,4CA332,4CA335,4CA336,4CA41A,4CA98B,4CA98C,4CA98D,4CA98E,4CA98F
 */