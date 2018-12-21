<?php

ini_set("memory_limit", "1024M");
require 'vendor/autoload.php';
use Ramsey\Uuid\Uuid;

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

$sql = "SELECT * FROM debitos.dbo.imputacao";
$return = $pdo->query($sql);
$query = $return->fetchAll();

foreach ($query as $row) {
    $valor = -1;
    $valor = $row["valor_imputado"];
    if ($valor != -1) {
        $idParcela = Uuid::uuid4();
        $IdImputacao = $row["idImputacao"];
        $parcela = 1;
        $juros = 0;
        $correcao = 0;

        $data_vencimento = $row["data_imputacao"];
        $ts1 = strtotime($data_vencimento);
        $ts1 = $ts1 + (30 * 24 * 60 * 60); // +30 dias
        $datetimeFormat = 'Y-m-d H:i:s';
        $date = new \DateTime();
        $date->setTimestamp($ts1);
        $data_vencimento = $date->format($datetimeFormat);

        $situacao = $row["valor_imputado"]; // to do, if se situação = quitado entao Q se n P
        if ($situacao == "EBF385AF-DBD4-4F14-A87F-C65BDF96C543") {
            $situacao = 'Q';
        } else {
            $situacao = 'A';
        }

        $tipo = "75246B20-3113-4B59-99EF-35459ED7661F";

        $uuid_imputacao = $row["idImputacao"];

        $sql_valorProcesso = "SELECT * from valoresResponsaveis.dbo.ValorProcesso where UUID = '$uuid_imputacao'";
        $return_valorProcesso = $pdo->query($sql_valorProcesso);
        $query_valorProcesso = $return_valorProcesso->fetchAll();

        foreach ($query_valorProcesso as $row_valorProcesso) {
            $idValorProcesso = $row_valorProcesso["idValorProcesso"];

            $sql_data = "SELECT * from valoresResponsaveis.dbo.valorRecuperado where idValorProcesso = $idValorProcesso";
            $return_data = $pdo->query($sql_data);
            $query_data = $return_data->fetchAll();

            foreach ($query_data as $row_data) {
                $data_pagamento = null;
                $data_pagamento = $row_data["dtRecuperacao"];
                $valorPago = null;
                $valorPago = $row_data["valor"];
            }

            $sql_valor_responsavel = "SELECT * from valoresResponsaveis.dbo.ValorResponsavel where idValorProcesso = $idValorProcesso";
            $return_valor_responsavel = $pdo->query($sql_valor_responsavel);
            $query_valor_responsavel = $return_valor_responsavel->fetchAll();

            foreach ($query_valor_responsavel as $row_valor_responsavel) {

                $idPF = $row_valor_responsavel["idResponsavel"];
                $idPJ = $row_valor_responsavel["idResponsavelPJ"];

                if ($idPF != null) {
                    $sql_pf = "SELECT * from valoresResponsaveis.dbo.Responsavel WHERE idResponsavel = $idPF";
                    $return_pf = $pdo->query($sql_pf);
                    $query_pf = $return_pf->fetchAll();

                    foreach ($query_pf as $row_pf) {
                        $uuid_responsavel = $row_pf["UUID"];
                    }
                } else {
                    $sql_pj = "SELECT * from valoresResponsaveis.dbo.ResponsavelPJ WHERE idResponsavelPJ = $idPJ";
                    $return_pj = $pdo->query($sql_pj);
                    $query_pj = $return_pj->fetchAll();

                    foreach ($query_pj as $row_pj) {
                        $uuid_responsavel = $row_pj["UUID"];
                    }
                }
            }

        }

        $insert = "INSERT INTO debitos.dbo.Parcela VALUES " .
            "('$idParcela','$IdImputacao',$parcela,$valor,$juros,$correcao,'$data_vencimento','$situacao','$data_pagamento',$valorPago,'$uuid_responsavel','$tipo')";
        $pdo->query($insert);
        echo " " . $insert . "\n";
    }

}
