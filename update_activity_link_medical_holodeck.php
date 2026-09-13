<?php if (PHP_SAPI !== 'cli') { http_response_code(404); exit; } ?>
<?php
require_once 'config.php';
$newLink = 'https://store.steampowered.com/app/754010/Medicalholodeck_Spatial_OS_obuchat_xirurgii_sozdavat_czifrovye_dvojniki_s_pomoshhyu_II_i_DICOM_i_izuchat_anatomiyu_cheloveka/';
$searchExact = 'Medical Holodeck';
try {
    $stmt = $pdo->prepare("SELECT ID_Attivita, Titolo, Link_WebXR FROM attivita_eventi WHERE LOWER(Titolo)=LOWER(?) OR Titolo LIKE ?");
    $stmt->execute([$searchExact, '%' . $searchExact . '%']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) === 0) {
        echo "Nessuna attività trovata con titolo '$searchExact'.\n";
        exit;
    }

    echo "Trovate " . count($rows) . " attività corrispondenti:\n";
    foreach ($rows as $r) {
        echo "- ID: {$r['ID_Attivita']} | Titolo: {$r['Titolo']} | Link_old: " . ($r['Link_WebXR'] ?: '(vuoto)') . "\n";
    }

    foreach ($rows as $r) {
        $upd = $pdo->prepare('UPDATE attivita_eventi SET Link_WebXR = ? WHERE ID_Attivita = ?');
        $upd->execute([$newLink, $r['ID_Attivita']]);
        echo "Aggiornata ID {$r['ID_Attivita']} => $newLink\n";
    }

    echo "\nFatto. Verifica con attivita_dettaglio.php?id=<ID>&debug=1\n";
} catch (PDOException $e) {
    echo 'Errore DB: ' . htmlspecialchars($e->getMessage());
}
