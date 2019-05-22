--TEST--
swoole_http_server_coro: keepalive
--SKIPIF--
<?php
require __DIR__ . '/../include/skipif.inc';
?>
--FILE--
<?php
require __DIR__ . '/../include/bootstrap.php';

Swoole\Runtime::enableCoroutine();

go(function () {
    $server = new Co\Http\Server("127.0.0.1", TCP_SERVER_PORT, false);
    $server->handle('/', function ($request, $response) {
        $response->end(serialize($request->server));
    });
    $server->handle('/shutdown', function ($request, $response) use ($server) {
        $response->end("shutdown");
        $server->shutdown();
    });
    $server->start();
});

go(function () {
    $cli = new Swoole\Coroutine\Http\Client('127.0.0.1', TCP_SERVER_PORT, false);
    $cli->set(['timeout' => 5]);
    \Swoole\Assert::assert($cli->get('/hello?a=x3'));
    $data1 = unserialize($cli->body);
    \Swoole\Assert::assert($cli->get('/world/index?b=455'));
    $data2 = unserialize($cli->body);
    \Swoole\Assert::assert($data1['remote_port'] == $data2['remote_port']);
    file_get_contents('http://127.0.0.1:' . TCP_SERVER_PORT . '/shutdown');
});

swoole_event_wait();
?>
--EXPECT--

