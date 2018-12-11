<?php

$db =  [
    'host' => '',
    'user' => '',
    'pass' => '',
    'dbname' => '',
];


try {
    $pdo = new PDO('dblib:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['pass']);
    echo "conectado\n";
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}