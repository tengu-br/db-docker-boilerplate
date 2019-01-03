<?php

require 'vendor/autoload.php';

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;


$db =  [
    'host' => '10.9.16.220',
    'user' => 'sa',
    'pass' => 'now-tcdf',
    'dbname' => 'master',
];


try {
    $pdo = new PDO('dblib:host=' . $db['host'] . ';dbname=' . $db['dbname'], $db['user'], $db['pass']);
    echo "conectado\n";
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

 $pdo->setAttribute(PDO::DBLIB_ATTR_STRINGIFY_UNIQUEIDENTIFIER, true);



$sql= "SELECT * FROM debitos.dbo.imputacao";
$return = $pdo -> query($sql);
$query = $return -> fetchAll();

foreach ($query as $row) {
    $idEvento = Uuid::uuid4();
    
    $fk_idImputacao = $row['idImputacao'];
    $edoc = $row ['edoc'];
    echo "ID Evento: " . $idEvento. "\n". "ID Imutacao: " . $fk_idImputacao . "\n" . "Edoc: " . $edoc . "\n";
    
    $sql_processo = "SELECT * FROM valoresResponsaveis.dbo.ValorProcesso WHERE UUID = '$fk_idImputacao'";
    $return_processo = $pdo->query($sql_processo);
    $query_processo = $return_processo->fetchAll();
    

    foreach ($query_processo as $row_processo) {
        $idValorProcesso = $row_processo ['idValorProcesso'];
    
        $sql_recuperado = "SELECT * FROM valoresResponsaveis.dbo.ValorRecuperado WHERE idValorProcesso = '$idValorProcesso'";
        $return_recuperado = $pdo->query($sql_recuperado);
        $query_recuperado = $return_recuperado->fetchAll();


        foreach ($query_recuperado as $row_recuperado) {
            $valor = $row_recuperado['valor'];
            $data = $row_recuperado['dtRecuperacao'];
            echo "Valor: " . $valor . "\n" . "Data: " . $data . "\n";
            
        }
         
    }

    $sql_parcela = "SELECT * FROM debitos.dbo.Parcela WHERE idImputacao = '$fk_idImputacao'";
    $return_parcela = $pdo->query($sql_parcela);
    $query_parcela = $return_parcela->fetchAll();

    foreach ($query_parcela as $row_parcela) {
         $fk_idParcela = $row_parcela['idParcela'];
         echo "ID Parcela: " . $fk_idParcela . "\n";
    }

    $fk_tipoEvento = 1;
    echo "Tipo Evento: " . $fk_tipoEvento . "\n";
    
    

    if ($valor != null && $data != null){   
    $sql_insert = "INSERT INTO debitos.dbo.Evento VALUES ('$idEvento', '$fk_idImputacao', $fk_tipoEvento, '$edoc', $valor, '$fk_idParcela', '$data')";
    $return4 = $pdo->query($sql_insert);
    echo $sql_insert;
    }
    
} 

 ?>