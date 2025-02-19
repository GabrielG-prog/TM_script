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
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'infonatclimanif') {
            $data = new SimpleXMLElement($xml->readOuterXML());
            
            $idmanif = $data->idmanif;
            $idnatcl = $data->idnatcl;
            $info_fr = $data->info_fr;
            $info_en = $data->info_en;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticketmaster_infonatclimanif WHERE idmanif = :idmanif LIMIT 1");
            $stmt->execute(['idmanif' => $idmanif]);
            
            if ($stmt->fetchColumn() > 0) {
                $updateStmt = $pdo->prepare("UPDATE ticketmaster_infonatclimanif SET idnatcl = :idnatcl, info_fr=:info_fr, info_en=:info_en  WHERE idmanif = :idmanif LIMIT 1");
                $updateStmt->execute([
                    'idnatcl' => $idnatcl,
                    'info_fr' => $info_fr,
                    'info_en' => $info_en,
                    'idmanif' => $idmanif
                ]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO ticketmaster_infonatclimanif (idmanif,idnatcl,info_fr,info_en)
                VALUES (:idmanif,:idnatcl,:info_fr,:info_en)");
                $insertStmt->execute([
                    'idmanif' => $idmanif,
                    'idnatcl' => $idnatcl,
                    'info_fr' => $info_fr,
                    'info_en' => $info_en
                ]);
            }
        }
    }
    
    $pdo->commit();
    $xml->close();
}
