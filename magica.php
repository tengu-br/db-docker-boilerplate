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

// $sql = "SELECT * FROM valoresResponsaveis.dbo.Responsavel where dtObito is not null";
// $sql1 = $pdo->query($sql);
// $sql2 = $sql1->fetchAll();

// foreach ($sql2 as $row) {
//     //echo $row["nomeMae"] . "\n";

//     $aux = $row["idResponsavel"];

//     $sql3 = "SELECT * FROM valoresResponsaveis.dbo.valorResponsavel";
//     $sql4 = $pdo->query($sql3);
//     $sql5 = $sql4->fetchAll();
//     echo  $row["dtObito"] . "\n";

//     foreach ($sql5 as $row2) {

//         if ($row["idResponsavel"] == $row2["idResponsavel"]) {
//             //echo $row2["idValorProcesso"] . "\n";

//             $idValorProcesso = $row2["idValorProcesso"];

//             $sql6 = "SELECT * FROM valoresResponsaveis.dbo.valorProcesso WHERE idValorProcesso = $idValorProcesso";
//             $sql7 = $pdo->query($sql6);
//             $sql8 = $sql7->fetchAll();
//             foreach ($sql8 as $row3) {
//                 $processo = $row3["idProcesso"];
//                 $auxiliar = $row3["idTipoValor"];
//                 $uuid = $row3["UUID"];

//                 if ($auxiliar == 3 || $auxiliar == 14 || $auxiliar == 16 || $auxiliar == 17) {

//                     print_r($row3) ;

//                     //print($processo) . "\n";

//                     // pegar o idprocesso de $row3 e fazer um for each
//                     // pegar o primeiro UUID do resultado da query acima
//                     //to be continued
//                 }
//             }
//         }
//     }
// }

// die();
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
$sql_imputacao = $pdo->query($sql_valorresponsavel);
$sql_imputacao_1 = $sql_imputacao->fetchAll();

foreach ($sql_imputacao_1 as $row_imputacao) {
    $uuid_imputacao = $row_imputacao['idImputacao'];
    $sql_valorProcesso = "SELECT * FROM valoresResponsaveis.dbo.valorProcesso WHERE UUID='$uuid_imputacao'";
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
                $sql = "SELECT * FROM valoresResponsaveis.dbo.Responsavel WHERE idResponsavel=$idResponsavel ";
                $sql_cpf = $pdo->query($sql);
                $sql_cpf_1 = $sql_cpf->fetchAll();

                foreach ($sql_cpf_1 as $row) {
                    $dataObito = null;
                    $idOrgao = null;
                    $cargo = null;
                    $motivo = null;

                    $cpfcnpj = $row['cpf'];
                    $dataObito = $row['dtObito'];

                    $id = $row['idResponsavel'];
                    $idResponsavel = $row["UUID"];

                    $query = "SELECT * FROM valoresResponsaveis.dbo.ResponsavelJurisdicionado WHERE idResponsavel=$id"; //ResponsavelJurisdicionado
                    $query1 = $pdo->query($query);
                    $sql_Jurisdicionado = $query1->fetchAll();

                    foreach ($sql_Jurisdicionado as $row_Jurisdicionado) {
                        $idOrgao = $row_Jurisdicionado["idOrgao"];
                        $cargo = $row_Jurisdicionado["funcao"];
                        $motivo = $row_Jurisdicionado["observacao"];
                    }

                    //echo "Pessoa Fisica ------->" . "\n";

                    if ($dataObito != null) {
                        echo "------------------------------------------------------------------\n" . "\n";
                        echo "MORTO:\n" . "\n";
                        echo $dataObito . "\n" . "\n";

                        $stringMes = substr($dataObito,0,3);
                        $stringDia = substr($dataObito,3,3);
                        $stringAno = substr($dataObito,6,6);

                        $stringMes = trim($stringMes);
                        $stringDia = trim($stringDia);
                        $stringAno = trim($stringAno);
                    

                        if($stringMes == 'Jan'){
                            $mes = '01';
                        }elseif($stringMes == 'Feb'){
                            $mes = '02';
                        }elseif($stringMes == 'Mar'){
                            $mes = '03';
                        }elseif($stringMes == 'May'){
                            $mes = '04';
                        }elseif($stringMes == 'Apr'){
                            $mes = '05';
                        }elseif($stringMes == 'Jun'){
                            $mes = '06';
                        }elseif($stringMes == 'Jul'){
                            $mes = '07';
                        }elseif($stringMes == 'Aug'){
                            $mes = '08';
                        }elseif($stringMes == 'Sep'){
                            $mes = '09';
                        }elseif($stringMes == 'Oct'){
                            $mes = '10';
                        }elseif($stringMes == 'Nov'){
                            $mes = '11';
                        }elseif($stringMes == 'Dec'){
                            $mes = '12';
                        }



                        echo ">".$mes . "<\n>". $stringDia ."<\n>". $stringAno . "<\n";
                        // echo ">".$mes . "<\n";
                        
                        $dataObito = $stringAno.'-'.$mes.'-'.$stringDia;
                        $insert = "INSERT INTO debitos.dbo.Imputacao_Responsavel(idResponsavelImputacao, idImputacao, cargo, motivo,idOrgao, cpfcnpj, dataObito) VALUES ('$idResponsavel', '$uuid_imputacao', '$cargo','$motivo', '$idOrgao', $cpfcnpj, '$dataObito')";
                        echo $insert. "\n";
                        $pdo->query($insert);
                    } else {
                        $insert = "INSERT INTO debitos.dbo.Imputacao_Responsavel(idResponsavelImputacao, idImputacao, cargo, motivo,idOrgao, cpfcnpj) VALUES ('$idResponsavel', '$uuid_imputacao', '$cargo','$motivo', '$idOrgao', $cpfcnpj)";
                        echo $insert . "\n";
                        $pdo->query($insert);
                    }
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

                    //echo "PJ \n\n";
                    $insert = "INSERT INTO debitos.dbo.Imputacao_Responsavel(idResponsavelImputacao, idImputacao,  cpfcnpj) VALUES ('$idResponsavelPJ', '$uuid_imputacao', $cpfcnpj)";
                    echo $insert . "\n";
                    $pdo->query($insert);
                }
            }
        }
    }
}
