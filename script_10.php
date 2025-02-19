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
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'optionmanif') {
            $data = new SimpleXMLElement($xml->readOuterXML());
            
            $idseanc = $data->idseanc;
            $idmanif = $data->idmanif;
            $indism= $data->indism;
            $indbcc=$data->indbcc;
            $datouvte_manif= $data->datouvte_manif != "" ? converteDate($data->datouvte_manif) : null;
            $indbenef =$data->indbenef;
            $datouvte_seanc=  $data->datouvte_seanc != "" ? converteDate($data->datouvte_seanc) : null;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ticketmaster_optionmanif WHERE idseanc = :idseanc LIMIT 1");
            $stmt->execute(['idseanc' => $idseanc]);
            
            if ($stmt->fetchColumn() > 0) {
                $updateStmt = $pdo->prepare("UPDATE ticketmaster_optionmanif SET idmanif=:idmanif, indism=:indism, indbcc=:indbcc, datouvte_manif=:datouvte_manif, indbenef=:indbenef, datouvte_seanc=:datouvte_seanc WHERE idseanc = :idseanc LIMIT 1");
                $updateStmt->execute([
                    'idseanc' => $idseanc,
                    'idmanif' => $idmanif,
                    'indism' => $indism,
                    'indbcc' => $indbcc,
                    'datouvte_manif' => $datouvte_manif,
                    'indbenef' => $indbenef,
                    'datouvte_seanc' => $datouvte_seanc
                ]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO ticketmaster_optionmanif (`idseanc`, `idmanif`, `indism`, `indbcc`, `datouvte_manif`, `indbenef`, `datouvte_seanc`)
          VALUES (:idseanc, :idmanif, :indism, :indbcc, :datouvte_manif, :indbenef, :datouvte_seanc)");
                $insertStmt->execute([
                    'idseanc' => $idseanc,
                    'idmanif' => $idmanif,
                    'indism' => $indism,
                    'indbcc' => $indbcc,
                    'datouvte_manif' => $datouvte_manif,
                    'indbenef' => $indbenef,
                    'datouvte_seanc' => $datouvte_seanc
                ]);
            }
        }
    }
    
    $pdo->commit();
    $xml->close();
}
