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
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'region') {
            $data = new SimpleXMLElement($xml->readOuterXML());
            
            $codreg= $data->codreg;
            $codpays= $data->codpays;
            $llgreg= $data->llgreg;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticketmaster_region WHERE codreg = :codreg AND codpays = :codpays LIMIT 1");
            $stmt->execute(['codreg' => $codreg, 'codpays' => $codpays]);
            
            if ($stmt->fetchColumn() > 0) {
                $updateStmt = $pdo->prepare("UPDATE ticketmaster_region SET codpays=:codpays, llgreg=:llgreg WHERE codreg = :codreg LIMIT 1");
                $updateStmt->execute([
                    'codpays' => $codpays,
                    'llgreg' => $llgreg,
                    'codreg' => $codreg
                ]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO ticketmaster_region (`codreg`, `codpays`, `llgreg`)
                VALUES (:codreg, :codpays, :llgreg)");
                $insertStmt->execute([
                    'codreg' => $codreg,
                    'codpays' => $codpays,
                    'llgreg' => $llgreg
                ]);
            }
        }
    }
    
    $pdo->commit();
    $xml->close();
}
