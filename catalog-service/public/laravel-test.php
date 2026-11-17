<?php

ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=UTF-8');

echo "ETAPE 1 : PHP OK\n";

try {
    require __DIR__.'/../vendor/autoload.php';
    echo "ETAPE 2 : AUTOLOAD OK\n";

    $app = require __DIR__.'/../bootstrap/app.php';
    echo "ETAPE 3 : BOOTSTRAP OK\n";

    $request = Illuminate\Http\Request::create('/api/health', 'GET');

    echo "ETAPE 4 : REQUETE CREEE\n";

    $response = $app->handleRequest($request);

    echo "ETAPE 5 : REPONSE RECUE\n";
    echo "STATUS : ".$response->getStatusCode()."\n";
    echo "CONTENU : ".$response->getContent()."\n";

} catch (Throwable $e) {
    echo "ERREUR : ".$e->getMessage()."\n";
    echo "FICHIER : ".$e->getFile()."\n";
    echo "LIGNE : ".$e->getLine()."\n";
}