<?php if (PHP_SAPI !== 'cli') { http_response_code(404); exit; } ?>
<?php
require_once 'config.php';

echo "<h2>Crea attività 'Test 3D'</h2>\n";

$partnerName = 'Demo 3D';
$partnerEmail = 'demo3d@openhouse.local';
$attivitaTitolo = 'Test 3D';
$viewerUrl = 'simulazione_test_3d.html';
$modelUrl = 'mdeli%203d/AttenuationTest.glb';

try {
    $stmt = $pdo->prepare("DELETE FROM attivita_eventi WHERE Titolo = ?");
    $stmt->execute(['Simulatore di Volo']);

    $stmt = $pdo->prepare("SELECT ID_Ente FROM istituti_e_partner WHERE Stato_Validazione = 1 AND (Tipologia LIKE '%AZIENDA%' OR Tipologia LIKE '%PARTNER%' OR Tipologia LIKE '%FSL%' OR Tipologia LIKE '%FSR%') ORDER BY ID_Ente ASC LIMIT 1");
    $stmt->execute();
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$partner) {
        $stmt = $pdo->prepare("SELECT ID_Ente FROM istituti_e_partner WHERE Ragione_Sociale = ? LIMIT 1");
        $stmt->execute([$partnerName]);
        $partner = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$partner) {
            $stmt = $pdo->prepare("INSERT INTO istituti_e_partner (Ragione_Sociale, Tipologia, Email, Indirizzo, Comune, Provincia, Regione, Stato_Validazione)
                                   VALUES (?, 'AZIENDA', ?, 'Via Demo 3D 1', 'Milano', 'MI', 'LOMBARDIA', 1)");
            $stmt->execute([$partnerName, $partnerEmail]);
            $partnerId = (int) $pdo->lastInsertId();
            echo "✓ Partner demo creato: {$partnerName} (ID: {$partnerId})<br>\n";
        } else {
            $partnerId = (int) $partner['ID_Ente'];
            $pdo->prepare("UPDATE istituti_e_partner SET Stato_Validazione = 1 WHERE ID_Ente = ?")->execute([$partnerId]);
            echo "✓ Partner demo già presente e validato: {$partnerName} (ID: {$partnerId})<br>\n";
        }
    } else {
        $partnerId = (int) $partner['ID_Ente'];
        echo "✓ Partner validato selezionato (ID: {$partnerId})<br>\n";
    }

    $stmt = $pdo->prepare("SELECT ID_Attivita FROM attivita_eventi WHERE FK_Ente_Organizzatore = ? AND Titolo = ? LIMIT 1");
    $stmt->execute([$partnerId, $attivitaTitolo]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    $dataOra = date('Y-m-d H:i:s', strtotime('+3 days'));
    $descrizione = 'Attività dimostrativa 3D con il modello AttenuationTest.glb, pensata per la simulazione e l\'orientamento digitale.';

    if (!$existing) {
        $stmt = $pdo->prepare("INSERT INTO attivita_eventi (FK_Ente_Organizzatore, Titolo, Descrizione, Link_WebXR, Data_Ora, Max_Posti, Flag_FSL, Tipo_Attivita, Durata_Minuti, Supporta_VR, Materiali_URL, Stato)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $partnerId,
            $attivitaTitolo,
            $descrizione,
            $viewerUrl,
            $dataOra,
            25,
            0,
            'laboratorio',
            45,
            1,
            $modelUrl,
            'pubblicata'
        ]);
        $attivitaId = (int) $pdo->lastInsertId();
        echo "✓ Attività creata (ID: {$attivitaId})<br>\n";
    } else {
        $attivitaId = (int) $existing['ID_Attivita'];
        $stmt = $pdo->prepare("UPDATE attivita_eventi SET Descrizione = ?, Link_WebXR = ?, Data_Ora = ?, Max_Posti = ?, Flag_FSL = ?, Tipo_Attivita = ?, Durata_Minuti = ?, Supporta_VR = ?, Materiali_URL = ?, Stato = ? WHERE ID_Attivita = ?");
        $stmt->execute([
            $descrizione,
            $viewerUrl,
            $dataOra,
            25,
            0,
            'laboratorio',
            45,
            1,
            $modelUrl,
            'pubblicata',
            $attivitaId
        ]);
        echo "✓ Attività aggiornata (ID: {$attivitaId})<br>\n";
    }

    echo "<p><strong>Modello:</strong> {$modelUrl}</p>\n";
    echo "<p><strong>Viewer:</strong> {$viewerUrl}</p>\n";
    echo "<p><a href=\"attivita_dettaglio.php?id={$attivitaId}&lang=it\">Apri attività Test 3D</a></p>\n";
    echo "<p><a href=\"attivita_elenco.php?lang=it\">Vai a elenco attività</a></p>\n";
} catch (Exception $e) {
    echo "❌ Errore: " . $e->getMessage();
}
?>
