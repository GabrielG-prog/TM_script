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
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'ssgenre') {
            $data = new SimpleXMLElement($xml->readOuterXML());
            
            $llgssgen= $data->llgssgen;
            $codssgen= $data->codssgen;
            $llcssgen= $data->llcssgen;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticketmaster_ssgenre WHERE llgssgen = :llgssgen LIMIT 1");
            $stmt->execute(['llgssgen' => $llgssgen]);
            
            if ($stmt->fetchColumn() > 0) {
                $updateStmt = $pdo->prepare("UPDATE ticketmaster_ssgenre SET codssgen=:codssgen, llcssgen=:llcssgen WHERE llgssgen = :llgssgen LIMIT 1");
                $updateStmt->execute([
                    'codssgen' => $codssgen,
                    'llcssgen' => $llcssgen,
                    'llgssgen' => $llgssgen
                ]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO ticketmaster_ssgenre (`llgssgen`, `codssgen`, `llcssgen`)
                VALUES (:llgssgen, :codssgen, :llcssgen)");
                $insertStmt->execute([
                    'llgssgen' => $llgssgen,
                    'codssgen' => $codssgen,
                    'llcssgen' => $llcssgen

                ]);
            }
        }
    }
    
    $pdo->commit();
    $xml->close();
}
