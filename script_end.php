<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pdo = new PDO("mysql:host=localhost;dbname=db_etimoc", "root", "root");

function getTicketmasterManifs($pdo) {
    $query = "SELECT idmanif, codgenre, codssgen FROM ticketmaster_manif";
    $stmt = $pdo->query($query);
    $manifs = [];
    while ($row = $stmt->fetch()) {
        $manifs[$row['idmanif']] = ['codgenre' => $row['codgenre'], 'codssgen' => $row['codssgen']];
    }
    return $manifs;
}

function getSsGenres($pdo) {
    $query = "SELECT id, codssgen FROM ticketmaster_ssgenre";
    return $pdo->query($query)->fetchAll();
}

function ticketmaster_convert_categories($codgenre, $codssgen, $ssgenre_array) {
    $categories_principales = [
        'LO' => 73, 'TH' => 73, 'CO' => 73, 'SP' => 73, 'DA' => 73, 'EX' => 73,
        'PA' => 30, 'SO' => 73, 'PK' => 73, 'SC' => 73, 'OP' => 73, 'CN' => 34,
        'SA' => 73, 'CI' => 73, 'HU' => 73, 'FE' => 73
    ];
    $categorie_principale = $categories_principales[$codgenre] ?? 0;
    $categorie_secondaire = 0;

    foreach ($ssgenre_array as $ssgenre) {
        if ($ssgenre['codssgen'] === $codssgen) {
            $categorie_secondaire = $ssgenre['id'];
            break;
        }
    }

    return [
        'categorie_principale' => $categorie_principale,
        'categorie_secondaire' => $categorie_secondaire,
    ];
}

function updateCategories($pdo, $manifs, $ssgenre_array) {
    $query = "SELECT id, ticketmaster_idmanif FROM offres_presentation WHERE categorie_principale = 0";
    $offres = $pdo->query($query)->fetchAll();

    $updateQuery = "UPDATE offres_presentation 
                    SET categorie_principale = :categorie_principale, 
                        categorie_secondaire = :categorie_secondaire 
                    WHERE id = :id";
    $stmt = $pdo->prepare($updateQuery);

    $pdo->beginTransaction();
    foreach ($offres as $offre) {
        $idmanif = $offre['ticketmaster_idmanif'];
        $categories = ticketmaster_convert_categories(
            $manifs[$idmanif]['codgenre'] ?? '',
            $manifs[$idmanif]['codssgen'] ?? '',
            $ssgenre_array
        );

        $stmt->execute([
            ':categorie_principale' => $categories['categorie_principale'],
            ':categorie_secondaire' => $categories['categorie_secondaire'],
            ':id' => $offre['id']
        ]);
    }
    $pdo->commit();
}

function updatePrices($pdo) {
    $query = "SELECT id, ticketmaster_idmanif FROM offres_presentation WHERE prix_mini = 0 AND visible = 1 LIMIT 1000";
    $offres = $pdo->query($query);

    $getTarifQuery = "SELECT montant FROM ticketmaster_tarif WHERE idmanif = :idmanif ORDER BY montant ASC LIMIT 1";
    $updateQuery = "UPDATE offres_presentation SET prix_mini = :prix_mini WHERE id = :id LIMIT 1";

    $getTarifStmt = $pdo->prepare($getTarifQuery);
    $updateStmt = $pdo->prepare($updateQuery);

    $pdo->beginTransaction();
    while ($offre = $offres->fetch()) {
        $getTarifStmt->execute([':idmanif' => $offre['ticketmaster_idmanif']]);
        $tarif = $getTarifStmt->fetchColumn() ?? 0;

        $updateStmt->execute([
            ':prix_mini' => $tarif,
            ':id' => $offre['id']
        ]);
    }
    $pdo->commit();
}

function updateLocations($pdo) {
    $query = "SELECT id, ticketmaster_idmanif FROM offres_presentation WHERE ville IS NULL AND region IS NULL AND visible = 1 LIMIT 1000";
    $offres = $pdo->query($query);

    $getInfoQuery = "SELECT codreg, llgville FROM ticketmaster_manif WHERE idmanif = :idmanif LIMIT 1";
    $getRegionQuery = "SELECT llgreg FROM ticketmaster_region WHERE codreg = :codreg LIMIT 1";
    $updateQuery = "UPDATE offres_presentation SET ville = :ville, region = :region WHERE id = :id LIMIT 1";

    $getInfoStmt = $pdo->prepare($getInfoQuery);
    $getRegionStmt = $pdo->prepare($getRegionQuery);
    $updateStmt = $pdo->prepare($updateQuery);

    $pdo->beginTransaction();
    while ($offre = $offres->fetch()) {
        $getInfoStmt->execute([':idmanif' => $offre['ticketmaster_idmanif']]);
        $info = $getInfoStmt->fetch();

        $ville = $info['llgville'] ?? null;
        $region = null;
        if (!empty($info['codreg'])) {
            $getRegionStmt->execute([':codreg' => $info['codreg']]);
            $region = $getRegionStmt->fetchColumn();
        }

        $updateStmt->execute([
            ':ville' => $ville,
            ':region' => $region,
            ':id' => $offre['id']
        ]);
    }
    $pdo->commit();
}

$manifs = getTicketmasterManifs($pdo);
$ssgenre_array = getSsGenres($pdo);
updateCategories($pdo, $manifs, $ssgenre_array);
updatePrices($pdo);
updateLocations($pdo);

$importQuery = "SELECT id FROM ticketmaster_import WHERE termine = 0 LIMIT 1";
$import = $pdo->query($importQuery)->fetch();
if ($import) {
    $updateImportQuery = "UPDATE ticketmaster_import SET termine = 1 WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($updateImportQuery);
    $stmt->execute([':id' => $import['id']]);
}