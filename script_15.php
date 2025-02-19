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
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'typemanif') {
            $data = new SimpleXMLElement($xml->readOuterXML());
            
            $codtypmnf= $data->codtypmnf;
            $llgtypmnf= $data->llgtypmnf;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticketmaster_typemanif WHERE codtypmnf = :codtypmnf LIMIT 1");
            $stmt->execute(['codtypmnf' => $codtypmnf]);
            
            if ($stmt->fetchColumn() > 0) {
                $updateStmt = $pdo->prepare("UPDATE ticketmaster_typemanif SET llgtypmnf = :llgtypmnf WHERE codtypmnf = :codtypmnf LIMIT 1");
                $updateStmt->execute([
                    'llgtypmnf' => $llgtypmnf,
                    'codtypmnf' => $codtypmnf
                ]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO ticketmaster_typemanif (codtypmnf,llgtypmnf)
                VALUES (:codtypmnf,:llgtypmnf)");
                $insertStmt->execute([
                    'codtypmnf' => $codtypmnf,
                    'llgtypmnf' => $llgtypmnf
                ]);
            }
        }
    }
    
    $pdo->commit();
    $xml->close();
}
