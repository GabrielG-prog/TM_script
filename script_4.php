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
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'genre') {
            $data = new SimpleXMLElement($xml->readOuterXML());
            
            $codgenre = $data->codgenre;
            $llggenre = $data->llggenre;
            $llcgenre = $data->llcgenre;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticketmaster_genre WHERE codgenre = :codgenre LIMIT 1");
            $stmt->execute(['codgenre' => $codgenre]);
            
            if ($stmt->fetchColumn() > 0) {
                $updateStmt = $pdo->prepare("UPDATE ticketmaster_genre SET llggenre=:llggenre, llcgenre=:llcgenre WHERE codgenre = :codgenre LIMIT 1");
                $updateStmt->execute([
                    'llggenre' => $llggenre,
                    'llcgenre' => $llcgenre,
                    'codgenre' => $codgenre
                ]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO ticketmaster_genre (`codgenre`, `llggenre`, `llcgenre`)
                VALUES (:codgenre, :llggenre, :llcgenre)");
                $insertStmt->execute([
                    'codgenre' => $codgenre,
                    'llggenre' => $llggenre,
                    'llcgenre' => $llcgenre
                ]);
            }
        }
    }
    
    $pdo->commit();
    $xml->close();
}
