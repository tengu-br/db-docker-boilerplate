<?php

ini_set("memory_limit", "1024M");

require 'vendor/autoload.php';

$db = [
    'host' => '10.9.16.220',
    'user' => 'sa',
    'pass' => 'now-tcdf',
    'dbname' => 'master',
];

try {
    $pdo = new PDO('dblib:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['pass']);

    echo "conectado\n";

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

$pdo->setAttribute(PDO::DBLIB_ATTR_STRINGIFY_UNIQUEIDENTIFIER, true);

// $sql = "SELECT * FROM valoresResponsaveis.dbo.ValorProcesso";
// $return = $pdo->query($sql);
// $query = $return->fetchAll();

// foreach ($query as $row) {

//     $id = $row["idProcesso"];

//     $sql2 = "SELECT * FROM valoresResponsaveis.dbo.ValorProcesso WHERE idProcesso = $id";
//     $return2 = $pdo->query($sql);
//     $query2 = $return2->fetchAll();
//     $flag3 = 0;
//     $flag14 = 0;

//     foreach ($query2 as $row2) {

//         if ($row2["idTipoValor"] == 3) {

//             $flag3 = 1;
//         }
//         if ($row2["idTipoValor"] == 14 || $row["idTipoValor"] == 16 || $row["idTipoValor"] == 17) {

//             $flag14 = 1;
//         }

//     }
//     if($flag3 ==1 && $flag14 ==1)
//     {

//         echo $row["idValorProcesso"] . "\n";
//     }

// }

// die();

// $pdo->query("TRUNCATE TABLE debitos.dbo.imputacao");

$sql = "SELECT * FROM valoresResponsaveis.dbo.ValorProcesso ";
$return = $pdo->query($sql);
$query = $return->fetchAll();

foreach ($query as $row) {

    if ($row["idTipoValor"] == 3 || $row["idTipoValor"] == 14 || $row["idTipoValor"] == 16 || $row["idTipoValor"] == 17) {

        $flag_registro_novo = 1;
        $idNatureza = "adcfae94-7641-4b4f-abb7-36c8fe8e1a4f"; //tudo debito
        $idSituacao = "0BD297A4-0776-41F8-A060-4982A3943D6B"; // Apuração, inadiplente, imputação, quitado, Em cobrança executiva !!!!! pendente !!!!!

        // $IdImputacao = Uuid::uuid4();
        $IdImputacao = $row["UUID"]; //id imputação uuid

        $idValorProcesso = $row["idValorProcesso"];
        $data_imputacao = $row["dtValor"];

        //pegar nr e ano pelo idProcesso, buscando no etcdf
        $sql_Valordecisao = "SELECT * FROM valoresResponsaveis.dbo.ValorDecisao WHERE idValorProcesso = $idValorProcesso";
        $query1 = $pdo->query($sql_Valordecisao);
        $query_ValorDecisao = $query1->fetchAll();

        $edoc = 0;
        $numProcesso = 0;
        $ano_processo = 0;

        foreach ($query_ValorDecisao as $row_decisao) {

            $idDocumento = $row_decisao["idDecisao"];
            $prazo = $row_decisao["prazo"];

            //pesquisa idDecisao no idDocumento Base de dados ETCDF
            $sql_etcdf = "SELECT eDoc,numero,ano FROM etcdf.dbo.documento WHERE idDocumento=$idDocumento";
            $query2 = $pdo->query($sql_etcdf);
            $query_etcdf = $query2->fetchAll();

            foreach ($query_etcdf as //gerando UUID

                $row_etcdf) {

                $edoc = $row_etcdf["eDoc"];
                $numProcesso = $row_etcdf["numero"];
                $ano_processo = $row_etcdf["ano"];

            }

        }
        //termina nr e ano pelo etcdf

        //obtendo dtCiencia
        //pesquisa base de dados ValorResponsavel
        $sql_ValorResponsavel = "SELECT dtCiencia FROM valoresResponsaveis.dbo.ValorResponsavel WHERE idValorProcesso = $idValorProcesso";
        $query3 = $pdo->query($sql_ValorResponsavel);
        $query_ValorResponsavel = $query3->fetchAll();
        $data_notificacao = null;

        foreach ($query_ValorResponsavel as $row_ValorResponsavel) {

            $data_notificacao = $row_ValorResponsavel["dtCiencia"];

        }
        //dtCiencia

        //determinar se é valor apurado ou valor imputado
        if ($row["idTipoValor"] == 3) {

            $valor_apurado = $row["valor"];
            $flag_apuracao = 1;

        } else {
            $valor_imputado = $row["valor"];
            $flag_apuracao = 0;
        }

        //vendo se já existe um uuid do processo no banco novo, se sim, ele usa o uuid existente
        $idProcesso = $row["idProcesso"];

        $sql_idProcesso = "SELECT * FROM valoresResponsaveis.dbo.ValorProcesso WHERE idProcesso = $idProcesso";
        $return_idprocesso = $pdo->query($sql_idProcesso);
        $query_idProcesso = $return_idprocesso->fetchAll();

        foreach ($query_idProcesso as $row_processo) {
            if (($row_processo["idValorProcesso"] < $row["idValorProcesso"]) && ($row_processo["idTipoValor"] == 3 || $row_processo["idTipoValor"] == 14 || $row_processo["idTipoValor"] == 16 || $row_processo["idTipoValor"] == 17)) {
                $IdImputacao = $row_processo["UUID"];
                // echo $row_processo["idValorProcesso"] . "|". $row["idValorProcesso"] . "\n";
                $flag_registro_novo = 0;
                break;
            }
        }

        // echo $IdImputacao."|".$idValorProcesso."|".$numProcesso."|".$ano_processo."|".$data_imputacao."|".$valor_imputado."|".$edoc."|".$prazo."|".$data_notificacao."|".$idNatureza."|".$idSituacao."|". "\n";
        //registros finais

        if ($flag_apuracao == 1 && $flag_registro_novo == 1) {
            //insert se é registro novo e apuracao
            $sql_query_debitos = "INSERT INTO debitos.dbo.imputacao (idImputacao, numProcesso,ano_processo,data_imputacao, valor_apurado, edoc, prazo,data_notificacao, idNatureza, idSituacao ) " .
                "VALUES ('$IdImputacao','$numProcesso','$ano_processo','$data_imputacao','$valor_apurado' ,'$edoc','$prazo','$data_notificacao','$idNatureza','$idSituacao')";
            $pdo->query($sql_query_debitos);
            //  echo $sql_query_debitos ."\n";
            echo "INSERT apuração: " . "UUID: " . $idValorProcesso . " valor: " . $valor_apurado . "\n";
        } elseif ($flag_apuracao == 1 && $flag_registro_novo == 0) {
            //insert se é registro existente e apuracao
            $sql_query_debitos = "UPDATE debitos.dbo.imputacao SET valor_apurado= $valor_apurado WHERE idImputacao = '$IdImputacao' ";
            $pdo->query($sql_query_debitos);
            echo $sql_query_debitos . "\n";
            echo "UPDATE apuração: " . "UUID: " . $idValorProcesso . " valor: " . $valor_apurado . "\n";

        } elseif ($flag_apuracao == 0 && $flag_registro_novo == 1) {
            //insert se é registro novo e imputacao
            $sql_query_debitos = "INSERT INTO debitos.dbo.imputacao (idImputacao, numProcesso,ano_processo,data_imputacao, valor_imputado, edoc, prazo,data_notificacao, idNatureza, idSituacao ) " .
                "VALUES ('$IdImputacao','$numProcesso','$ano_processo','$data_imputacao','$valor_imputado','$edoc','$prazo','$data_notificacao','$idNatureza','$idSituacao')";
            $pdo->query($sql_query_debitos);
            // echo $sql_query_debitos."\n";
            echo "INSERT imputação: " . "UUID: " . $idValorProcesso . " valor: " . $valor_imputado . "\n";
        } else {
            //insert se é registro existente e imputacao
            $sql_query_debitos = "UPDATE debitos.dbo.imputacao SET valor_imputado = $valor_imputado WHERE idImputacao = '$IdImputacao' ";
            $pdo->query($sql_query_debitos);
            echo $sql_query_debitos . "\n";
            echo "UPDATE imputação: " . "UUID: " . $idValorProcesso . " valor: " . $valor_imputado . "\n";
        }

    } else {

        echo "iD " . $row["idValorProcesso"] . " descartado. \n";

    }

}
