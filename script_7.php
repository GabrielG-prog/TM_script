<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require './db_and_zip.php';

foreach ($xmlFiles as $file) {
    $xml = new XMLReader();
    $xml->open($file);
    
    $pdo->beginTransaction();
    
    while ($xml->read()) {
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'modconf') {
            $data = new SimpleXMLElement($xml->readOuterXML());
            
            $idmanif= $data->idmanif;
            $idseanc = $data->idseanc;
            $codcatpl= $data->codcatpl;
            $modconf= $data->modconf;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticketmaster_modconf WHERE idmanif = :idmanif LIMIT 1");
            $stmt->execute(['idmanif' => $idmanif]);
            
            if ($stmt->fetchColumn() > 0) {
                $updateStmt = $pdo->prepare("UPDATE ticketmaster_modconf SET idseanc=:idseanc,codcatpl = :codcatpl,modconf = :modconf WHERE idmanif = :idmanif LIMIT 1");
                $updateStmt->execute([
                    'idseanc' => $idseanc,
                    'codcatpl' => $codcatpl,
                    'modconf' => $modconf,
                    'idmanif' => $idmanif
                ]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO ticketmaster_modconf (idmanif,idseanc,codcatpl,modconf)
                VALUES (:idmanif,:idseanc,:codcatpl,:modconf)");
                $insertStmt->execute([
                    'idmanif' => $idmanif,
                    'idseanc' => $idseanc,
                    'codcatpl' => $codcatpl,
                    'modconf' => $modconf
                ]);
            }
        }
    }
    
    $pdo->commit();
    $xml->close();
}
