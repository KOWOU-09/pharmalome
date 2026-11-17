<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

    $request = Illuminate\Http\Request::create(
        '/api/health',
        'GET',
        [],
        [],
        [],
        ['HTTP_ACCEPT' => 'application/json']
    );

    $response = $kernel->handle($request);

    echo "LARAVEL : OK\n";
    echo "STATUS : ".$response->getStatusCode()."\n";
    echo "CONTENT TYPE : ".$response->headers->get('Content-Type')."\n";
    echo "BODY : ".$response->getContent()."\n";

    $kernel->terminate($request, $response);

} catch (Throwable $e) {
    echo "ERREUR : ".$e->getMessage()."\n";
    echo "FICHIER : ".$e->getFile()."\n";
    echo "LIGNE : ".$e->getLine()."\n";
}