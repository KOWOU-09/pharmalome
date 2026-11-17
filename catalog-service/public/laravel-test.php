<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=UTF-8');

echo "START\n";

try {
    require __DIR__.'/../vendor/autoload.php';
    echo "AUTOLOAD_OK\n";

    $app = require __DIR__.'/../bootstrap/app.php';
    echo "BOOTSTRAP_OK\n";
} catch (Throwable $e) {
    echo "ERROR: ".get_class($e)."\n";
    echo $e->getMessage()."\n";
}
