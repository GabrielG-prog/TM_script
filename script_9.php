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
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'motcle') {
            $data = new SimpleXMLElement($xml->readOuterXML());
            
            $idmanif = $node->idmanif;
            $motcle = $node->motcle;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticketmaster_motcle WHERE motcle = :motcle LIMIT 1");
            $stmt->execute(['motcle' => $motcle]);
            
            if ($stmt->fetchColumn() > 0) {
                $updateStmt = $pdo->prepare("UPDATE ticketmaster_motcle SET idmanif=:idmanif WHERE motcle = :motcle LIMIT 1");
                $updateStmt->execute([
                    'idmanif' => $idmanif,
                    'motcle' => $motcle
                ]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO ticketmaster_motcle (`idmanif`, `motcle`)
                VALUES (:idmanif, :motcle)");
                $insertStmt->execute([
                    'idmanif' => $idmanif,
                    'motcle' => $motcle
                ]);
            }
        }
    }
    
    $pdo->commit();
    $xml->close();
}
