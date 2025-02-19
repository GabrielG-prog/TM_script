<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require './db_and_zip.php';

$nb_ligne_seance = 0;
$timestampDay = strtotime(date('d-m-Y 00:00:00'));
$timestamp = strtotime(date('d-m-Y H:i:s'));
$id_tm_import = 0;

function converteDate($value) {
    return strtotime(str_replace("/", "-", $value));
}

$get_tm_import = $pdo->prepare("SELECT id FROM ticketmaster_import WHERE termine = 0 LIMIT 1");
$get_tm_import->execute();
$tm_import = $get_tm_import->fetch();

if ($tm_import) {
    $id_tm_import = (int) $tm_import['id'];
} else {
    $request_tm_creer = $pdo->prepare("INSERT INTO ticketmaster_import (date_import) VALUES (:date_import)");
    $request_tm_creer->bindParam(':date_import', $timestampDay, PDO::PARAM_INT);
    $request_tm_creer->execute();
    $id_tm_import = (int) $pdo->lastInsertId();
}

function addSeance(PDO $pdo, int $id, string $pourcentage_seance, int $nb_ligne_seance): void {
    $stmt = $pdo->prepare(
        "UPDATE ticketmaster_import 
         SET nombreSeance = nombreSeance + :nb_ligne_seance, 
             pourcentage_seance = :pourcentage_seance 
         WHERE id = :id LIMIT 1"
    );
    $stmt->execute([
        ':id' => $id,
        ':pourcentage_seance' => $pourcentage_seance,
        ':nb_ligne_seance' => $nb_ligne_seance
    ]);
}

foreach ($xmlFiles as $file) {
    $xml = new XMLReader();
    $xml->open($file);
    $batchSize = 100;
    $batchCount = 0;

    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
    }

    $insertStmt = $pdo->prepare(
        "INSERT INTO ticketmaster_seance 
         (idmanif, idseanc, llgseanc, datseanc, codstatm, nbbill, tauxtva, tauxtvafrais, eticket, zoning, indopen, mticket, last_import)
         VALUES (:idmanif, :idseanc, :llgseanc, :datseanc, :codstatm, :nbbill, :tauxtva, :tauxtvafrais, :eticket, :zoning, :indopen, :mticket, :last_import)"
    );

    $updateStmt = $pdo->prepare(
        "UPDATE ticketmaster_seance 
         SET idmanif=:idmanif, llgseanc=:llgseanc, datseanc=:datseanc, codstatm=:codstatm, nbbill=:nbbill, 
             tauxtva=:tauxtva, tauxtvafrais=:tauxtvafrais, eticket=:eticket, zoning=:zoning, indopen=:indopen, 
             mticket=:mticket, last_import=:last_import 
         WHERE idseanc = :idseanc LIMIT 1"
    );

    $checkStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM ticketmaster_seance WHERE idmanif = :idmanif AND idseanc = :idseanc LIMIT 1"
    );

    while ($xml->read()) {
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'seance') {
            $data = new SimpleXMLElement($xml->readOuterXML());
            
            $params = [
                'idmanif' => $data->idmanif,
                'idseanc' => $data->idseanc,
                'llgseanc' => $data->llgseanc,
                'datseanc' => converteDate($data->datseanc . " " . $data->heurseanc),
                'codstatm' => $data->codstatm,
                'nbbill' => $data->nbbill,
                'tauxtva' => $data->tauxtva,
                'tauxtvafrais' => $data->tauxtvafrais ?: null,
                'eticket' => intval($data->eticket),
                'zoning' => $data->zoning,
                'indopen' => $data->indopen,
                'mticket' => $data->mticket,
                'last_import' => $timestamp
            ];

            $checkStmt->execute(['idmanif' => $params['idmanif'], 'idseanc' => $params['idseanc']]);

            if ($checkStmt->fetchColumn() > 0) {
                $updateStmt->execute($params);
            } else {
                $insertStmt->execute($params);
            }

            $nb_ligne_seance++;
            $batchCount++;

            if ($batchCount >= $batchSize) {
                $pdo->commit();
                $pdo->beginTransaction();
                $batchCount = 0;
            }
        }
    }
    if ($batchCount > 0 && $pdo->inTransaction()) {
        $pdo->commit();
    }
    $xml->close();
}
addSeance($pdo, $id_tm_import, '0', $nb_ligne_seance);