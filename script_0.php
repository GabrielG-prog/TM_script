<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require './db_and_zip.php';

$timestampDay = strtotime(date('d-m-Y 00:00:00'));
$timestamp = strtotime(date('d-m-Y H:i:s'));
$debut = microtime(true);

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
    $request_tm_creer->bindValue(':date_import', $timestampDay, PDO::PARAM_INT);
    $request_tm_creer->execute();
    $id_tm_import = (int) $pdo->lastInsertId();
}

function updateCounter(PDO $pdo, int $id, string $field) {
    $request = "UPDATE ticketmaster_import SET $field = $field + 1 WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($request);
    $stmt->execute([':id' => $id]);
}

$checkManifStmt = $pdo->prepare("SELECT COUNT(*) FROM ticketmaster_manif WHERE idmanif = :idmanif LIMIT 1");
$updateManifStmt = $pdo->prepare("UPDATE ticketmaster_manif SET llgmanif = :llgmanif, codstatm = :codstatm, codtypmnf = :codtypmnf,
    debmanif = :debmanif, finmanif = :finmanif, codgenre = :codgenre, codssgen = :codssgen, idlieu = :idlieu, 
    llglieu = :llglieu, llclieu = :llclieu, adr1lieu = :adr1lieu, adr2lieu = :adr2lieu, llgville = :llgville, 
    codreg = :codreg, codpost = :codpost, codpays = :codpays WHERE idmanif = :idmanif LIMIT 1");
$insertManifStmt = $pdo->prepare("INSERT INTO ticketmaster_manif (idmanif, llgmanif, codstatm, codtypmnf, debmanif, finmanif, codgenre, codssgen, idlieu, llglieu, llclieu, adr1lieu, adr2lieu, llgville, codreg, codpost, codpays)
    VALUES (:idmanif, :llgmanif, :codstatm, :codtypmnf, :debmanif, :finmanif, :codgenre, :codssgen, :idlieu, :llglieu, :llclieu, :adr1lieu, :adr2lieu, :llgville, :codreg, :codpost, :codpays)");
$checkPresentationStmt = $pdo->prepare("SELECT 1 FROM offres_presentation WHERE ticketmaster_idmanif = :ticketmaster_idmanif LIMIT 1");
$updatePresentationStmt = $pdo->prepare("UPDATE offres_presentation SET nom = :nom, date_debut = :date_debut, date_fin = :date_fin, last_import = :last_import WHERE ticketmaster_idmanif = :ticketmaster_idmanif LIMIT 1");
$insertPresentationStmt = $pdo->prepare("INSERT INTO offres_presentation (ticketmaster_idmanif, customer_id, nom, type, date_debut, date_fin, date_creation, last_import) VALUES (:ticketmaster_idmanif, :customer_id, :nom, :type, :date_debut, :date_fin, :date_creation, :last_import)");

foreach ($xmlFiles as $file) {
    $xml = new XMLReader();
    $xml->open($file);
    $pdo->beginTransaction();

    while ($xml->read()) {
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'manif') {
            $data = new SimpleXMLElement($xml->readOuterXML());
            
            $params = [
                'idmanif' => $data->idmanif,
                'llgmanif' => $data->llgmanif,
                'codstatm' => $data->codstatm,
                'codtypmnf' => $data->codtypmnf,
                'debmanif' => converteDate($data->debmanif),
                'finmanif' => converteDate($data->finmanif),
                'codgenre' => $data->codgenre,
                'codssgen' => $data->codssgen,
                'idlieu' => $data->idlieu,
                'llglieu' => $data->llglieu,
                'llclieu' => $data->llclieu,
                'adr1lieu' => $data->adr1lieu,
                'adr2lieu' => $data->adr2lieu,
                'llgville' => $data->llgville,
                'codreg' => $data->codreg,
                'codpost' => $data->codpost,
                'codpays' => $data->codpays
            ];

            $checkManifStmt->execute(['idmanif' => $params['idmanif']]);

            if ($checkManifStmt->fetchColumn() > 0) {
                $updateManifStmt->execute($params);
            } else {
                $insertManifStmt->execute($params);
            }

            $checkPresentationStmt->execute(['ticketmaster_idmanif' => $params['idmanif']]);
            $exists = $checkPresentationStmt->fetchColumn();

            $presentationParams = [
                ':ticketmaster_idmanif' => $params['idmanif'],
                ':nom' => $params['llgmanif'],
                ':date_debut' => $params['debmanif'],
                ':date_fin' => $params['finmanif'],
                ':last_import' => $timestamp,
                ':customer_id' => 0,
                ':type' => 'ticketmaster',
                ':date_creation' => $debut
            ];

            if ($exists) {
                $updatePresentationStmt->execute($presentationParams);
                updateCounter($pdo, $id_tm_import, 'nombreManif');
            } else {
                $insertPresentationStmt->execute($presentationParams);
                updateCounter($pdo, $id_tm_import, 'nombreManifCreer');
            }
        }
    }

    $pdo->commit();
    $xml->close();
}
