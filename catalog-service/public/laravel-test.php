<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

try {
    $server = $_SERVER;
    $server['REQUEST_URI'] = '/api/health';
    $server['SCRIPT_NAME'] = '/index.php';

    $request = Illuminate\Http\Request::createFromGlobals();

    $kernel = $app->make(
        Illuminate\Contracts\Http\Kernel::class
    );

    $response = $kernel->handle($request);

    echo "URI : ".$request->getPathInfo()."\n";
    echo "STATUS : ".$response->getStatusCode()."\n";
    echo "TYPE : ".$response->headers->get('Content-Type')."\n";
    echo "BODY : ".$response->getContent()."\n";

    $kernel->terminate($request, $response);

} catch (Throwable $e) {
    echo "ERREUR : ".$e->getMessage()."\n";
}