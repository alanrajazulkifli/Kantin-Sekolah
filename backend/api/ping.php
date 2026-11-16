<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

setHeaders();

$db = (new Database())->connect();
$version = $db->query('SELECT VERSION() AS v')->fetch();

jsonResponse(true, 'API aktif', [
    'php'   => PHP_VERSION,
    'mysql' => $version['v'],
    'time'  => date('c'),
]);