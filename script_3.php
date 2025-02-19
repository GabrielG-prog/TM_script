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
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'catplace') {
            $data = new SimpleXMLElement($xml->readOuterXML());
            
            $codcatpl = $node->codcatpl;
            $llgcatpl = $node->llgcatpl;
            $llccatpl = $node->llccatpl;
            $coulrgb = $node->coulrgb;
            $coulhexa = $node->coulhexa;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticketmaster_catplace WHERE codcatpl = :codcatpl LIMIT 1");
            $stmt->execute(['codcatpl' => $codcatpl]);
            
            if ($stmt->fetchColumn() > 0) {
                $updateStmt = $pdo->prepare("UPDATE ticketmaster_catplace SET llgcatpl = :llgcatpl, llccatpl=:llccatpl, coulrgb=:coulrgb, coulhexa=:coulhexa  WHERE codcatpl = :codcatpl LIMIT 1");
                $updateStmt->execute([
                    'llgcatpl' => $llgcatpl,
                    'llccatpl' => $llccatpl,
                    'coulrgb' => $coulrgb,
                    'coulhexa' => $coulhexa,
                    'codcatpl' => $codcatpl
                ]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO ticketmaster_catplace (codcatpl,llgcatpl,llccatpl,coulrgb,coulhexa)
        VALUES (:codcatpl,:llgcatpl,:llccatpl,:coulrgb,:coulhexa)");
                $insertStmt->execute([
                    'codcatpl' => $codcatpl,
                    'llgcatpl' => $llgcatpl,
                    'llccatpl' => $llccatpl,
                    'coulrgb' => $coulrgb,
                    'coulhexa' => $coulhexa
                ]);
            }
        }
    }
    
    $pdo->commit();
    $xml->close();
}
