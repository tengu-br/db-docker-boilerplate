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

$sql = "SELECT * FROM valoresResponsaveis.dbo.ValorProcesso";
$return = $pdo->query($sql);
$query = $return->fetchAll();

foreach ($query as $row) {

    if ($row["idTipoValor"] == 3 || $row["idTipoValor"] == 14 || $row["idTipoValor"] == 16 || $row["idTipoValor"] == 17) {

        $flag_registro_novo = 1;
        $edoc = 0;
        $numProcesso = 0;
        $ano_processo = 0;
        $valor_apurado = 0;
        $valor_imputado = 0;

        //multa tem apuração ?
        //da maneira atual multa é só "valor imputado"
        if ($row["idTipoValor"] == 3 || $row["idTipoValor"] == 16 || $row["idTipoValor"] == 17) {
            $idNatureza = "adcfae94-7641-4b4f-abb7-36c8fe8e1a4f"; //tudo debito
        } else {
            $idNatureza = "BEF3D7D5-EF28-4CE2-AA7E-94E30FA9B8B9"; //tudo multa
        }

        // $IdImputacao = Uuid::uuid4();
        $IdImputacao = $row["UUID"]; //id imputação uuid

        $idValorProcesso = $row["idValorProcesso"];

        //pegar nr e ano pelo idProcesso, buscando no etcdf
        $sql_Valordecisao = "SELECT * FROM valoresResponsaveis.dbo.ValorDecisao WHERE idValorProcesso = $idValorProcesso";
        $query1 = $pdo->query($sql_Valordecisao);
        $query_ValorDecisao = $query1->fetchAll();

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

        // vendo se já existe um uuid do processo no banco novo, se sim, ele usa o uuid existente
        // $sql_idProcesso = "SELECT * FROM valoresResponsaveis.dbo.ValorProcesso WHERE idProcesso = $idProcesso";
        // $return_idprocesso = $pdo->query($sql_idProcesso);
        // $query_idProcesso = $return_idprocesso->fetchAll();

        // $idProcesso = $row["idProcesso"];

        // foreach ($query_idProcesso as $row_processo) {
        //     if (($row_processo["idValorProcesso"] < $row["idValorProcesso"]) && ($row_processo["idTipoValor"] == 3 || $row_processo["idTipoValor"] == 14 || $row_processo["idTipoValor"] == 16 || $row_processo["idTipoValor"] == 17)) {
        //         $IdImputacao = $row_processo["UUID"];
        //         $flag_registro_novo = 0;
        //         break;
        //     }
        // }

        //acumulando valor de multiplas multas / debitos

        // foreach ($query_idProcesso as $row_processo) {
        //     if ($row_processo["idTipoValor"] == 3 && $row_processo["idTipoValor"] == $row["idTipoValor"]) {
        //         $valor_apurado = $valor_apurado + $row_processo["valor"];
        //         echo $valor_apurado . "\n";
        //     } elseif ($row_processo["idTipoValor"] == 14 && $row_processo["idTipoValor"] == $row["idTipoValor"]) {
        //         $valor_imputado = $valor_imputado + $row_processo["valor"];
        //     } elseif (($row_processo["idTipoValor"] == 16 || $row_processo["idTipoValor"] == 17) && (17 == $row["idTipoValor"] || 16 == $row["idTipoValor"])) {
        //         $valor_imputado = $valor_imputado + $row_processo["valor"];
        //         echo $valor_imputado . "\n";
        //     }
        // }

        //situação
        $idSituacao = null; // Apuração, inadiplente, imputação, quitado, Em cobrança executiva !!!!!

        // $sql_recuperado = "SELECT * FROM valoresResponsaveis.dbo.ValorProcesso  WHERE idProcesso = $idProcesso";
        // $return_recuperado = $pdo->query($sql_recuperado);
        // $query_recuperado = $return_recuperado->fetchAll();

        $valor_recuperado = 0;
        $data_imputacao = 0;

        //hoje
        $today = getdate();
        $datetimeFormat = 'Y-m-d H:i:s';
        $date = new \DateTime();
        $date->setTimestamp($today[0]);
        $aux = $date->format($datetimeFormat);

        $sql_valor = "SELECT * FROM valoresResponsaveis.dbo.ValorRecuperado  WHERE idValorProcesso = $idValorProcesso";
        $return_valor = $pdo->query($sql_valor);
        $query_valor = $return_valor->fetchAll();

        foreach ($query_valor as $row_valor) {
            $valor_recuperado = $valor_recuperado + $row_valor["valor"];
        }

        // foreach ($query_recuperado as $row_recuperado) {

        //     if ($row_recuperado["dtValor"] > $data_imputacao) {
        $data_imputacao = $row["dtValor"];
        //     }

        //     if ($row_recuperado["idTipoValor"] == 14 || $row_recuperado["idTipoValor"] == 16 || $row_recuperado["idTipoValor"] == 17) {

        //     }

        // }

        //diferença em dias das duas datas
        $ts1 = strtotime($data_imputacao);
        $ts2 = strtotime($aux);
        $diff = ($ts2 - $ts1) / 86400;

        if ($valor_imputado == null) {
            $idSituacao = "0BD297A4-0776-41F8-A060-4982A3943D6B"; //apuração
        } elseif ($valor_recuperado >= $valor_imputado) {
            $idSituacao = "EBF385AF-DBD4-4F14-A87F-C65BDF96C543"; //quitado
        } elseif ($diff <= 30) {
            $idSituacao = "0E5C9CB6-ADFA-4467-B78B-6A61B2A50DFE"; //imputação
        } else {
            $idSituacao = "D062B2EC-FBD0-4FA3-857F-590B04756369"; //inadimplente
        }
        // echo "<><><><><><>" .$idSituacao . "\n";

        // echo $IdImputacao."|".$idValorProcesso."|".$numProcesso."|".$ano_processo."|".$data_imputacao."|".$valor_imputado."|".$edoc."|".$prazo."|".$data_notificacao."|".$idNatureza."|".$idSituacao."|". "\n";
        //registros finais

        if ($flag_apuracao == 1 && $flag_registro_novo == 1) {
            //insert se é registro novo e apuracao
            if ($data_notificacao == null) {
                $sql_query_debitos = "INSERT INTO debitos.dbo.imputacao (idImputacao, numProcesso,ano_processo,data_imputacao, valor_apurado, edoc, prazo, idNatureza, idSituacao ) " .
                    "VALUES ('$IdImputacao','$numProcesso','$ano_processo','$data_imputacao','$valor_apurado' ,'$edoc','$prazo','$idNatureza','$idSituacao')";
                $pdo->query($sql_query_debitos);
                echo $sql_query_debitos . "\n";

            } else {

                $sql_query_debitos = "INSERT INTO debitos.dbo.imputacao (idImputacao, numProcesso,ano_processo,data_imputacao, valor_apurado, edoc, prazo,data_notificacao, idNatureza, idSituacao ) " .
                    "VALUES ('$IdImputacao','$numProcesso','$ano_processo','$data_imputacao','$valor_apurado' ,'$edoc','$prazo','$data_notificacao','$idNatureza','$idSituacao')";
                $pdo->query($sql_query_debitos);
                echo $sql_query_debitos . "\n";

            }
            // echo "INSERT apuração: " . "UUID: " . $idValorProcesso . " valor: " . $valor_apurado . "\n";
        } elseif ($flag_apuracao == 1 && $flag_registro_novo == 0) {

            //insert se é registro existente e apuracao
            $sql_query_debitos = "UPDATE debitos.dbo.imputacao SET valor_apurado= $valor_apurado,idSituacao = '$idSituacao' WHERE idImputacao = '$IdImputacao' ";
            $pdo->query($sql_query_debitos);
            echo $sql_query_debitos . "\n";
            // echo "UPDATE apuração: " . "UUID: " . $idValorProcesso . " valor: " . $valor_apurado . "\n";

        } elseif ($flag_apuracao == 0 && $flag_registro_novo == 1) {
            //insert se é registro novo e imputacao

            if ($data_notificacao == null) {
                $sql_query_debitos = "INSERT INTO debitos.dbo.imputacao (idImputacao, numProcesso,ano_processo,data_imputacao, valor_imputado, edoc, prazo, idNatureza, idSituacao ) " .
                    "VALUES ('$IdImputacao','$numProcesso','$ano_processo','$data_imputacao','$valor_imputado','$edoc','$prazo','$idNatureza','$idSituacao')";
                $pdo->query($sql_query_debitos);
                echo $sql_query_debitos . "\n";

            } else {

                $sql_query_debitos = "INSERT INTO debitos.dbo.imputacao (idImputacao, numProcesso,ano_processo,data_imputacao, valor_imputado, edoc, prazo,data_notificacao, idNatureza, idSituacao ) " .
                    "VALUES ('$IdImputacao','$numProcesso','$ano_processo','$data_imputacao','$valor_imputado','$edoc','$prazo','$data_notificacao','$idNatureza','$idSituacao')";
                $pdo->query($sql_query_debitos);
                echo $sql_query_debitos . "\n";

            }
            // echo "INSERT imputação: " . "UUID: " . $idValorProcesso . " valor: " . $valor_imputado . "\n";
        } else {
            //insert se é registro existente e imputacao
            $sql_query_debitos = "UPDATE debitos.dbo.imputacao SET valor_imputado = $valor_imputado, idSituacao = '$idSituacao' WHERE idImputacao = '$IdImputacao' ";
            $pdo->query($sql_query_debitos);
            echo $sql_query_debitos . "\n";
            // echo "UPDATE imputação: " . "UUID: " . $idValorProcesso . " valor: " . $valor_imputado . "\n";
        }

    } else {

        echo "iD " . $row["idValorProcesso"] . " descartado. \n";

    }

}


