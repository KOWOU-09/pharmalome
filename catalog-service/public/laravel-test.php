<?php

ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=UTF-8');

echo "ETAPE_1\n";

try {
    require __DIR__.'/../vendor/autoload.php';
    echo "ETAPE_2_AUTOLOAD_OK\n";

    $app = require __DIR__.'/../bootstrap/app.php';
    echo "ETAPE_3_BOOTSTRAP_OK\n";

    $request = Illuminate\Http\Request::create('/api/health', 'GET');

    echo "ETAPE_4_REQUEST_OK\n";

    $app->handleRequest($request);

    echo "\nETAPE_5_FIN\n";

} catch (Throwable $e) {
    echo "\nERREUR : ".get_class($e)."\n";
    echo $e->getMessage()."\n";
}