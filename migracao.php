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
    $pdo = new PDO('dblib:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['pass']);

    echo "conectado\n\n";
  

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

//gerando UUID

//    foreach ($cereja as $row) {

//      $uuid4 = Uuid::uuid4();
//      $cenoura=$row["idValorProcesso"];
//      $up= "UPDATE valoresResponsaveis.dbo.ValorProcesso SET UUID = '$uuid4' WHERE idValorProcesso = '$cenoura'";
//      $pdo->query($up);
//      echo $up . "\n";
// }

echo "\n";

$idNatureza=0;//table natureza debito ou multa
$idSituacao=0;// Apuração, inadiplente, imputação, quitado, Em cobrança executiva

$sql= "SELECT * FROM valoresResponsaveis.dbo.ValorProcesso";
$return= $pdo ->query($sql);
$query=$return->fetchAll();


foreach($query as $row)
{
    //id imputação uuid - aleatório
    $IdImputacao = Uuid::uuid4();
    
    $idValorProcesso= $row["idValorProcesso"];
    $data_imputacao=$row["dtValor"];
    $valor_imputado=$row["valor"];

    
    //busca valor recuperado
    $sql_ValorRecuperado="SELECT * FROM valoresResponsaveis.dbo.ValorRecuperado WHERE idValorProcesso = $idValorProcesso";    
    $query0=$pdo->query($sql_ValorRecuperado);
    $query_ValorRecuperado=$query0->fetchAll();
    $pago=0;

    foreach($query_ValorRecuperado as $row_ValorRecuperado){

        if($row_ValorRecuperado["idValorProcesso"]== $idValorProcesso){

            $pago=$pago+$row_ValorRecuperado["valor"];

        }
        

    }

 
    
    
    
    //pesquisa decisao com o id valor processo
    $sql_Valordecisao="SELECT * FROM valoresResponsaveis.dbo.ValorDecisao WHERE idValorProcesso = $idValorProcesso";    
    $query1=$pdo->query($sql_Valordecisao);
    $query_ValorDecisao=$query1->fetchAll();


    foreach($query_ValorDecisao as $row_decisao){

        
        $idDocumento=$row_decisao["idDecisao"];
        $prazo=$row_decisao["prazo"];

        //pesquisa idDecisao no idDocumento Base de dados ETCDF
        $sql_etcdf= "SELECT eDoc,numero,ano FROM etcdf.dbo.documento WHERE idDocumento=$idDocumento";        
        $query2=$pdo->query($sql_etcdf);
        $query_etcdf=$query2->fetchAll();
        

        foreach($query_etcdf as $row_etcdf){

            $edoc=$row_etcdf["eDoc"];
            $numProcesso=$row_etcdf["numero"];
            $ano_processo=$row_etcdf["ano"];           

        }  
        
        
        

    }


    //pesquisa base de dados  ValorResponsavel
    $sql_ValorResponsavel ="SELECT dtCiencia FROM valoresResponsaveis.dbo.ValorResponsavel WHERE idValorProcesso = $idValorProcesso";
    $query3=$pdo->query($sql_ValorResponsavel);
    $query_ValorResponsavel=$query3->fetchAll();

    //obtendo dtCiencia
    foreach($query_ValorResponsavel as $row_ValorResponsavel){

        
        $data_notificacao=$row_ValorResponsavel["dtCiencia"];
        
    }
    
        $sql_query_debitos="INSERT INTO debitos.dbo.imputacao (idimputação, numProcesso,ano_processo,data_imputacao, valor_imputado, edoc, prazo,data_notificacao, idNatureza, idSituacao ) ".
        "VALUES ($IdImputacao,$numProcesso,$ano_processo,$data_imputacao,$valor_imputado,$edoc,$prazo,$data_notificacao,$idNatureza,$idSituacao)";

        echo $sql_query_debitos. "\n";
        
    

   
}



?>