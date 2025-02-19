<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require './db_and_zip.php';

function converteDate($value) {
    return strtotime(str_replace("/", "-", $value));
}

$nb_ligne_tarif = 0;
$timestampDay = strtotime(date('d-m-Y 00:00:00'));
$timestamp = strtotime(date('d-m-Y H:i:s'));

$id_tm_import = (int) $pdo->query("SELECT id FROM ticketmaster_import WHERE termine = 0 LIMIT 1")->fetchColumn();

if (!$id_tm_import) {
    $stmt = $pdo->prepare("INSERT INTO ticketmaster_import (date_import) VALUES (:date_import)");
    $stmt->execute([':date_import' => $timestampDay]);
    $id_tm_import = (int) $pdo->lastInsertId();
}

function addTarif(PDO $pdo, int $id, int $nb_ligne_tarif): void {
    $stmt = $pdo->prepare(
        "UPDATE ticketmaster_import 
         SET nombreTarifs = nombreTarifs + :nb_ligne_tarif
         WHERE id = :id"
    );
    $stmt->execute([':id' => $id, ':nb_ligne_tarif' => $nb_ligne_tarif]);
}

foreach ($xmlFiles as $file) {
    $xml = new XMLReader();
    $xml->open($file);
    $pdo->beginTransaction();
    $insertData = [];

    while ($xml->read()) {
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name === 'tarif') {
            $data = new SimpleXMLElement($xml->readOuterXML());

            $row = [
                'idmanif' => $data->idmanif,
                'idseanc' => $data->idseanc,
                'idtarif' => $data->idtarif,
                'idgril' => $data->idgril,
                'debvtar' => converteDate($data->debvtar),
                'finvtar' => converteDate($data->finvtar),
                'idnatcl' =>  $data->idnatcl,
                'llgnatcl' => $data->llgnatcl,
                'codtypadh' => 0,
                'codcatpl' => $data->codcatpl,
                'llgcatpl' => $data->llgcatpl,
                'llccatpl' => $data->llccatpl,
                'montant' => $data->montant,
                'mntfrsM' => $data->mntfrsM,
                'indlimite' => $data->indlimite,
                'idformule' => $data->idformule,
                'llgformule' => $data->llgformule,
                'last_import' => $timestamp
            ];

            $exists = $pdo->prepare("SELECT 1 FROM ticketmaster_tarif WHERE idtarif = :idtarif AND idmanif = :idmanif AND idseanc = :idseanc LIMIT 1");
            $exists->execute(['idtarif' => $row['idtarif'], 'idmanif' => $row['idmanif'], 'idseanc' => $row['idseanc']]);

            if ($exists->fetch()) {
                $updateStmt = $pdo->prepare(
                    "UPDATE ticketmaster_tarif 
                     SET idgril = :idgril, debvtar = :debvtar, finvtar = :finvtar, idnatcl = :idnatcl, llgnatcl = :llgnatcl,
                         codtypadh = :codtypadh, codcatpl = :codcatpl, llgcatpl = :llgcatpl, llccatpl = :llccatpl, montant = :montant,
                         mntfrsM = :mntfrsM, indlimite = :indlimite, idformule = :idformule, llgformule = :llgformule, last_import = :last_import
                     WHERE idtarif = :idtarif"
                );
                $updateStmt->execute($row);
            } else {
                $insertData[] = $row;
            }
            $nb_ligne_tarif++;
        }
    }

    if ($insertData) {
        $columns = implode(", ", array_keys($insertData[0]));
        $placeholders = rtrim(str_repeat("(:idmanif, :idseanc, :idtarif, :idgril, :debvtar, :finvtar, :idnatcl, :llgnatcl, :codtypadh, :codcatpl, :llgcatpl, :llccatpl, :montant, :mntfrsM, :indlimite, :idformule, :llgformule, :last_import), ", count($insertData)), ", ");

        $stmt = $pdo->prepare("INSERT INTO ticketmaster_tarif ($columns) VALUES $placeholders");
        foreach ($insertData as $row) {
            $stmt->execute($row);
        }
    }

    $pdo->commit();
    $xml->close();
}

addTarif($pdo, $id_tm_import, $nb_ligne_tarif);
