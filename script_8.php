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
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'modret') {
            $data = new SimpleXMLElement($xml->readOuterXML());
            
            $codmodre = $data->codmodre;
            $llcmodre= $data->llcmodre;
            $llgmodre= $data->llgmodre;
            $codpdt= $data->codpdt;
            $llcpdt= $data->llcpdt;
            $llgpdt= $data->llgpdt;
            $mntpdt= $data->mntpdt;
            $edtlocale =intval($data->edtlocale);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticketmaster_modret WHERE codmodre = :codmodre LIMIT 1");
            $stmt->execute(['codmodre' => $codmodre]);
            
            if ($stmt->fetchColumn() > 0) {
                $updateStmt = $pdo->prepare("UPDATE ticketmaster_modret SET llcmodre=:llcmodre,llgmodre = :llgmodre,codpdt = :codpdt,llcpdt=:llcpdt,llgpdt=:llgpdt,mntpdt=:mntpdt,edtlocale=:edtlocale WHERE codmodre = :codmodre LIMIT 1");
                $updateStmt->execute([
                    'llcmodre' => $llcmodre,
                    'llgmodre' => $llgmodre,
                    'codpdt' => $codpdt,
                    'llcpdt' => $llcpdt,
                    'llgpdt' => $llgpdt,
                    'mntpdt' => $mntpdt,
                    'edtlocale' => $edtlocale,
                    'codmodre' => $codmodre
                ]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO ticketmaster_modret (codmodre,llcmodre,llgmodre,codpdt,llcpdt,llgpdt,mntpdt,edtlocale)
                VALUES (:codmodre,:llcmodre,:llgmodre,:codpdt,:llcpdt,:llgpdt,:mntpdt,:edtlocale)");
                $insertStmt->execute([
                    'codmodre' => $codmodre,
                    'llcmodre' => $llcmodre,
                    'llgmodre' => $llgmodre,
                    'codpdt' => $codpdt,
                    'llcpdt' => $llcpdt,
                    'llgpdt' => $llgpdt,
                    'mntpdt' => $mntpdt,
                    'edtlocale' => $edtlocale
                ]);
            }
        }
    }
    
    $pdo->commit();
    $xml->close();
}
