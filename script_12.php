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
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'populnatcli') {
            $data = new SimpleXMLElement($xml->readOuterXML());
            
            $codpopul= $data->codpopul;
            $llgpopul = $data->llgpopul;
            $llcpopul= $data->llcpopul;
            $idnatcl= $data->idnatcl;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticketmaster_populnatcli WHERE idnatcl = :idnatcl LIMIT 1");
            $stmt->execute(['idnatcl' => $idnatcl]);
            
            if ($stmt->fetchColumn() > 0) {
                $updateStmt = $pdo->prepare("UPDATE ticketmaster_populnatcli SET llgpopul=:llgpopul, llcpopul=:llcpopul, codpopul=:codpopul WHERE idnatcl = :idnatcl LIMIT 1");
                $updateStmt->execute([
                    'llgpopul' => $llgpopul,
                    'llcpopul' => $llcpopul,
                    'codpopul' => $codpopul,
                    'idnatcl' => $idnatcl
                ]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO ticketmaster_populnatcli (`codpopul`, `llgpopul`, `llcpopul`, `idnatcl`)
                VALUES (:codpopul, :llgpopul, :llcpopul, :idnatcl)");
                $insertStmt->execute([
                    'codpopul' => $codpopul,
                    'llgpopul' => $llgpopul,
                    'llcpopul' => $llcpopul,
                    'idnatcl' => $idnatcl
                ]);
            }
        }
    }
    $pdo->commit();
    $xml->close();
}
