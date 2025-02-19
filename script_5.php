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
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'natcli') {
            $data = new SimpleXMLElement($xml->readOuterXML());
            
            $idnatcl= $data->idnatcl;
            $llgnatcl= $data->llgnatcl;
            $llcnatcl= $data->llcnatcl;
            $indpromo= $data->indpromo;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticketmaster_natcli WHERE idnatcl = :idnatcl LIMIT 1");
            $stmt->execute(['idnatcl' => $idnatcl]);
            
            if ($stmt->fetchColumn() > 0) {
                $updateStmt = $pdo->prepare("UPDATE ticketmaster_natcli SET llgnatcl=:llgnatcl, llcnatcl=:llcnatcl, indpromo=:indpromo WHERE idnatcl = :idnatcl LIMIT 1");
                $updateStmt->execute([
                    'llgnatcl' => $llgnatcl,
                    'llcnatcl' => $llcnatcl,
                    'indpromo' => $indpromo,
                    'idnatcl' => $idnatcl
                ]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO ticketmaster_natcli (`llgnatcl`, `llcnatcl`, `indpromo`, `idnatcl`)
                VALUES (:llgnatcl, :llcnatcl, :indpromo, :idnatcl)");
                $insertStmt->execute([
                    'idnatcl' => $idnatcl,
                    'llgnatcl' => $llgnatcl,
                    'llcnatcl' => $llcnatcl,
                    'indpromo' => $indpromo
                ]);
            }
        }
    }
    
    $pdo->commit();
    $xml->close();
}
