<?php

$db =  [
    'host' => '10.9.16.220',
    'user' => 'sa',
    'pass' => 'now-tcdf',
    'dbname' => 'valoresResponsaveis',
];

$user = 'sa';
$password = 'now-tcdf';

try {
    $pdo = new PDO('dblib:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['pass']);
    echo "lol\n";
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}