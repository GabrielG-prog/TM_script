<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require './db_and_zip.php';

function converteDate($value) {
    return strtotime(str_replace("/", "-", $value));
}

function tronquer($texte, $n) {
    if (strlen($texte) > $n) {
        $texte = substr($texte, 0, $n);
        $position_espace = strrpos($texte, " ");
        $texte = substr($texte, 0, $position_espace) . '...';
    }
    return $texte;
}

foreach ($xmlFiles as $file) {
    $xml = new XMLReader();
    $xml->open($file);
    $pdo->beginTransaction();

    $updateStmt = $pdo->prepare("
        UPDATE ticketmaster_infomanif 
        SET presentation = :presentation, infoplace = :infoplace, infotarif = :infotarif, datmodi = :datmodi
        WHERE idmanif = :idmanif
    ");

    $insertStmt = $pdo->prepare("
        INSERT INTO ticketmaster_infomanif (idmanif, presentation, infoplace, infotarif, datmodi) 
        VALUES (:idmanif, :presentation, :infoplace, :infotarif, :datmodi)
    ");

    $checkOffreStmt = $pdo->prepare("
        SELECT 1 
        FROM offres_presentation 
        WHERE ticketmaster_idmanif = :ticketmaster_idmanif 
        LIMIT 1
    ");

    $updateOffreStmt = $pdo->prepare("
        UPDATE offres_presentation 
        SET contenu = :contenu, comment = :comment, resume = :resume 
        WHERE ticketmaster_idmanif = :ticketmaster_idmanif 
    ");

    while ($xml->read()) {
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == 'infomanif') {
            $data = new SimpleXMLElement($xml->readOuterXML());

            $idmanif = $data->idmanif;
            $presentation = $data->presentation;
            $infoplace = $data->infoplace;
            $infotarif = $data->infotarif;
            $datmodi = converteDate($data->datmodi);
            $resume = tronquer($presentation, 50);

            $updateStmt->execute([
                'presentation' => $presentation,
                'infoplace' => $infoplace,
                'infotarif' => $infotarif,
                'datmodi' => $datmodi,
                'idmanif' => $idmanif,
            ]);

            if ($updateStmt->rowCount() === 0) {
                $insertStmt->execute([
                    'idmanif' => $idmanif,
                    'presentation' => $presentation,
                    'infoplace' => $infoplace,
                    'infotarif' => $infotarif,
                    'datmodi' => $datmodi,
                ]);
            }

            $checkOffreStmt->execute(['ticketmaster_idmanif' => $idmanif]);
            if ($checkOffreStmt->fetchColumn() !== false) {
                $updateOffreStmt->execute([
                    'contenu' => $presentation,
                    'comment' => $infoplace,
                    'resume' => $resume,
                    'ticketmaster_idmanif' => $idmanif,
                ]);
            }
        }
    }

    $pdo->commit();
    $xml->close();
}
