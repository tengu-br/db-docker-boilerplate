<?php

require 'vendor/autoload.php';
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

//    Connect
$db =  [
    'host' => '10.9.16.220',
    'user' => 'sa',
    'pass' => 'now-tcdf',
    'dbname' => 'valoresResponsaveis',
];

try {
    $pdo = new PDO('dblib:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['pass']);
    echo "conectado\n";
} catch (PDOException $e) {
    echo 'erro conexao banco: ' . $e->getMessage();
}

// Select all
$sql="SELECT * FROM valoresResponsaveis.dbo.testefred";
$all= $pdo->query($sql);
$result = $all->fetchAll();

foreach($result as $row) {
//    Generate
    try {
        $uuid4 = Uuid::uuid4();
    } catch (UnsatisfiedDependencyException $e) {
        echo 'Caught ramsey exception: ' . $e->getMessage() . "\n";
    }
//    Update
    $cpf = $row["cpf"];
    $sql="UPDATE valoresResponsaveis.dbo.testefred SET UUID='$uuid4' WHERE cpf='$cpf'";
    $pdo->query($sql);
//    Print
    echo $row["cpf"] . "-" . $row["UUID"]. "\n";
}