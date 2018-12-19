<?php

require 'vendor/autoload.php';

//Conectando o banco de dados

$db =
    [
    'host' => '10.9.16.220',
    'user' => 'sa',
    'pass' => 'now-tcdf',
    'dbname' => 'master',
];

try
{
    $pdo = new PDO('dblib:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['pass']);
    echo "conectado\n";
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

$pdo->setAttribute(PDO::DBLIB_ATTR_STRINGIFY_UNIQUEIDENTIFIER, true); //resolver problema UUID bugado

// $sql_valorresponsavel="SELECT * FROM valoresResponsaveis.dbo.Responsavel";//idpj
// $sql_imputacao=$pdo->query($sql_valorresponsavel);
// $sql_imputacao_1=$sql_imputacao->fetchAll();

// foreach($sql_imputacao_1 as $row){
//     $id=$row["idResponsavel"];
//     $uuidpj= Uuid::uuid4();
//     $upd="UPDATE valoresResponsaveis.dbo.Responsavel SET UUID = '$uuidpj' WHERE idResponsavel = $id";
//     $pdo->query($upd);
//     echo $upd . "\n";
// }

// die();

$sql_valorresponsavel = "SELECT * FROM debitos.dbo.imputacao"; //Obtendo dados da nova tabela (imputacao)
$sql_imputacao = $pdo->query($sql_valorresponsavel); //Valor Responsavel
$sql_imputacao_1 = $sql_imputacao->fetchAll();

foreach ($sql_imputacao_1 as $row_imputacao) {

    $uuid_imputacao = $row_imputacao['idImputacao'];

    $sql_valorProcesso = "SELECT * FROM valoresResponsaveis.dbo.valorProcesso WHERE UUID='$uuid_imputacao'"; //ValorProcesso
    $sql_valorProcesso_1 = $pdo->query($sql_valorProcesso);
    $id_valorProcesso = $sql_valorProcesso_1->fetchAll();

    foreach ($id_valorProcesso as $row_idValorProcesso) {
        $idValorProcesso = $row_idValorProcesso["idValorProcesso"];

        $sql_valor_Responsavel = "SELECT * FROM valoresResponsaveis.dbo.valorResponsavel WHERE idValorProcesso=$idValorProcesso"; //ValorResponsavel
        $sql_valorResponsavel_1 = $pdo->query($sql_valor_Responsavel);
        $id_valorResponsavel = $sql_valorResponsavel_1->fetchAll();

        foreach ($id_valorResponsavel as $row_id_valor_responsavel) {
            // CPF
            if ($row_id_valor_responsavel['idResponsavel'] != null) {

                $idResponsavel = $row_id_valor_responsavel['idResponsavel'];

                $sql = "SELECT * FROM valoresResponsaveis.dbo.Responsavel WHERE idResponsavel=$idResponsavel "; //Responsavel
                $sql_cpf = $pdo->query($sql);
                $sql_cpf_1 = $sql_cpf->fetchAll();

                foreach ($sql_cpf_1 as $row) {

                    $dataObito = null; // DATA OBITO
                    $idOrgao = null;
                    $cargo = null;
                    $motivo = null;

                    $cpfcnpj = $row['cpf'];
                    $dataObito = $row['dtObito'];
                    $id = $row['idResponsavel'];
                    $idResponsavel = $row["UUID"];
                    // echo $dataObito . "\n";

                    $query = "SELECT * FROM valoresResponsaveis.dbo.ResponsavelJurisdicionado WHERE idResponsavel=$id"; //ResponsavelJurisdicionado
                    $query1 = $pdo->query($query);
                    $sql_Jurisdicionado = $query1->fetchAll();

                    foreach ($sql_Jurisdicionado as $row_Jurisdicionado) {
                        $idOrgao = $row_Jurisdicionado["idOrgao"];
                        $cargo = $row_Jurisdicionado["funcao"];
                        $motivo = $row_Jurisdicionado["observacao"];
                    }
                    echo "Pessoa Fisica \n\n";

                    $insert = "INSERT INTO debitos.dbo.Imputacao_Responsavel(idResponsavelImputacao, idImputacao, cargo, motivo,idOrgao, cpfcnpj, dataObito) VALUES ('$idResponsavel', '$uuid_imputacao', '$cargo','$motivo', '$idOrgao', $cpfcnpj,'$dataObito')";
                    echo $insert . "\n";
                    $pdo->query($insert);

                }
                //CNPJ
            } else {
                $idResponsavelPJ = $row_id_valor_responsavel['idResponsavelPJ'];

                $sql_cnpj = "SELECT * FROM valoresResponsaveis.dbo.ResponsavelPJ WHERE idResponsavelPJ=$idResponsavelPJ"; //ResponsavelPJ
                $sql_cnpj_1 = $pdo->query($sql_cnpj);
                $sql_responsavelPJ_1 = $sql_cnpj_1->fetchAll();

                foreach ($sql_responsavelPJ_1 as $row) {
                    $cpfcnpj = $row['cnpj'];
                    $id = $row['idResponsavelPJ'];
                    $idResponsavelPJ = $row['UUID'];

                    // $query="SELECT * FROM valoresResponsaveis.dbo.ResponsavelJurisdicionado WHERE idResponsavel=$id";
                    // $query1=$pdo->query($query);
                    // $sql_Jurisdicionado=$query1->fetchAll();

                    // $idOrgao=0;
                    // $cargo=0;
                    // $motivo=0;

                    // foreach($sql_Jurisdicionado as $row_Jurisdicionado)
                    // {
                    //     $idOrgao=$row_Jurisdicionado["idOrgao"];
                    //     $cargo=$row_Jurisdicionado["funcao"];
                    //     $motivo=$row_Jurisdicionado["observacao"];
                    // }

                    echo "PJ \n\n";
                    $insert = "INSERT INTO debitos.dbo.Imputacao_Responsavel(idResponsavelImputacao, idImputacao,  cpfcnpj) VALUES ('$idResponsavelPJ', '$uuid_imputacao', $cpfcnpj)";
                    echo $insert . "\n";
                    $pdo->query($insert);
                }
            }
        }
    }
}
