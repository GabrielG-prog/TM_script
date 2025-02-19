<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require './db_and_zip.php';

function converteDate($value) {
    return strtotime(str_replace("/", "-", $value));
}

foreach ($xmlFiles as $file) {
    $xml = new XMLReader();
    $xml->open($file);
    
    $pdo->beginTransaction();
    
    while ($xml->read()) {
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'pays') {
            $data = new SimpleXMLElement($xml->readOuterXML());
            
            $codpays= $data->codpays;
            $llgpays= $data->llgpays;
            $llgpays_en= $data->llgpays_en;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticketmaster_pays WHERE codpays = :codpays LIMIT 1");
            $stmt->execute(['codpays' => $codpays]);
            
            if ($stmt->fetchColumn() > 0) {
                $updateStmt = $pdo->prepare("UPDATE ticketmaster_pays SET llgpays=:llgpays, llgpays_en=:llgpays_en WHERE codpays = :codpays LIMIT 1");
                $updateStmt->execute([
                    'codpays' => $codpays,
                    'llgpays' => $llgpays,
                    'llgpays_en' => $llgpays_en
                ]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO ticketmaster_pays (`codpays`, `llgpays`, `llgpays_en`)
                VALUES (:codpays, :llgpays, :llgpays_en)");
                $insertStmt->execute([
                    'codpays' => $codpays,
                    'llgpays' => $llgpays,
                    'llgpays_en' => $llgpays_en
                ]);
            }
        }
    }
    
    $pdo->commit();
    $xml->close();
}
