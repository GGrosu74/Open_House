<?php if (PHP_SAPI !== 'cli') { http_response_code(404); exit; } ?>
<?php
require_once 'config.php';

echo "<h2>Inserimento Partner Demo</h2>";

try {
    $hasTelefono = tableHasColumn($pdo, 'istituti_e_partner', 'Telefono');

    // Prima controlla se ci sono già partner
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM istituti_e_partner WHERE Ragione_Sociale LIKE 'Prova%' OR Ragione_Sociale LIKE '%VR'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['cnt'] > 0) {
        echo "⚠️ Partner demo già esistono nel database (" . $result['cnt'] . " trovati)<br><br>";
        echo "Procedo con l'eliminazione e reinserimento...<br>";
        $pdo->exec("DELETE FROM istituti_e_partner WHERE Ragione_Sociale LIKE 'Prova%' OR Ragione_Sociale LIKE '%VR'");
        echo "✓ Partner vecchi eliminati<br>";
    }

    // Inserisci Partner FSL (con Cod_REA)
    if ($hasTelefono) {
        $pdo->exec("
            INSERT INTO istituti_e_partner (Ragione_Sociale, Tipologia, Email, Indirizzo, Comune, Provincia, Regione, Cod_REA, Telefono, Stato_Validazione) VALUES
            ('Prova Robototecnica', 'AZIENDA', 'info@prova-robotica.it', 'Via Roma 10', 'Palermo', 'PA', 'SICILIA', 'PA123456', NULL, 1),
            ('Prova Medicina', 'AZIENDA', 'info@prova-medicina.it', 'Via Garibaldi 25', 'Catania', 'CT', 'SICILIA', 'CT654321', NULL, 1),
            ('Prova Ingegneria Meccanica', 'AZIENDA', 'info@prova-ing.it', 'Via Mazzini 15', 'Messina', 'ME', 'SICILIA', 'ME789456', NULL, 1),
            ('Prova Scienze Biologiche', 'AZIENDA', 'info@prova-bio.it', 'Corso Vittorio 30', 'Agrigento', 'AG', 'SICILIA', 'AG456789', NULL, 1),
            ('Prova Architettura Sostenibile', 'AZIENDA', 'info@prova-arch.it', 'Viale Autonomia 12', 'Trapani', 'TP', 'SICILIA', 'TP321654', NULL, 1)
        ");
    } else {
        $pdo->exec("
            INSERT INTO istituti_e_partner (Ragione_Sociale, Tipologia, Email, Indirizzo, Comune, Provincia, Regione, Cod_REA, Stato_Validazione) VALUES
            ('Prova Robototecnica', 'AZIENDA', 'info@prova-robotica.it', 'Via Roma 10', 'Palermo', 'PA', 'SICILIA', 'PA123456', 1),
            ('Prova Medicina', 'AZIENDA', 'info@prova-medicina.it', 'Via Garibaldi 25', 'Catania', 'CT', 'SICILIA', 'CT654321', 1),
            ('Prova Ingegneria Meccanica', 'AZIENDA', 'info@prova-ing.it', 'Via Mazzini 15', 'Messina', 'ME', 'SICILIA', 'ME789456', 1),
            ('Prova Scienze Biologiche', 'AZIENDA', 'info@prova-bio.it', 'Corso Vittorio 30', 'Agrigento', 'AG', 'SICILIA', 'AG456789', 1),
            ('Prova Architettura Sostenibile', 'AZIENDA', 'info@prova-arch.it', 'Viale Autonomia 12', 'Trapani', 'TP', 'SICILIA', 'TP321654', 1)
        ");
    }

    echo "✓ Inseriti 5 Partner FSL<br>";

    // Inserisci Partner VR (con Tipologia ARENA_VR o PARTNER_VR)
    if ($hasTelefono) {
        $pdo->exec("
            INSERT INTO istituti_e_partner (Ragione_Sociale, Tipologia, Email, Indirizzo, Comune, Provincia, Regione, Telefono, Stato_Validazione) VALUES
            ('TechVision VR', 'ARENA_VR', 'info@techvision-vr.it', 'Via Innovazione 5', 'Palermo', 'PA', 'SICILIA', NULL, 1),
            ('ImmersiveSpace VR', 'PARTNER_VR', 'contact@immersivespace.it', 'Via Tecnologia 20', 'Catania', 'CT', 'SICILIA', NULL, 1),
            ('VirtualWorld Arena', 'ARENA_VR', 'hello@virtualworld.it', 'Via Realtà Virtuale 8', 'Messina', 'ME', 'SICILIA', NULL, 1),
            ('XR Solutions VR', 'PARTNER_VR', 'info@xr-solutions.it', 'Corso Digitale 42', 'Agrigento', 'AG', 'SICILIA', NULL, 1)
        ");
    } else {
        $pdo->exec("
            INSERT INTO istituti_e_partner (Ragione_Sociale, Tipologia, Email, Indirizzo, Comune, Provincia, Regione, Stato_Validazione) VALUES
            ('TechVision VR', 'ARENA_VR', 'info@techvision-vr.it', 'Via Innovazione 5', 'Palermo', 'PA', 'SICILIA', 1),
            ('ImmersiveSpace VR', 'PARTNER_VR', 'contact@immersivespace.it', 'Via Tecnologia 20', 'Catania', 'CT', 'SICILIA', 1),
            ('VirtualWorld Arena', 'ARENA_VR', 'hello@virtualworld.it', 'Via Realtà Virtuale 8', 'Messina', 'ME', 'SICILIA', 1),
            ('XR Solutions VR', 'PARTNER_VR', 'info@xr-solutions.it', 'Corso Digitale 42', 'Agrigento', 'AG', 'SICILIA', 1)
        ");
    }

    echo "✓ Inseriti 4 Partner VR<br>";
    echo "<br><strong>✓ Totale: 9 partner inseriti con successo!</strong><br>";
    echo "<br>";

    // Mostra un riepilogo
    echo "<h3>Riepilogo Partner Inseriti:</h3>";

    echo "<b>Partner FSL:</b><br>";
    $stmt = $pdo->query("SELECT Ragione_Sociale, Cod_REA FROM istituti_e_partner WHERE Cod_REA IS NOT NULL AND Cod_REA != '' AND Stato_Validazione = 1 ORDER BY Ragione_Sociale");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "- " . $row['Ragione_Sociale'] . " (Cod_REA: " . $row['Cod_REA'] . ")<br>";
    }

    echo "<br><b>Partner VR:</b><br>";
    $stmt = $pdo->query("SELECT Ragione_Sociale, Tipologia FROM istituti_e_partner WHERE Tipologia IN ('ARENA_VR', 'PARTNER_VR') AND Stato_Validazione = 1 ORDER BY Ragione_Sociale");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "- " . $row['Ragione_Sociale'] . " (" . $row['Tipologia'] . ")<br>";
    }

    echo "<br><br>";
    echo '<a href="partner_istituti.php">Visualizza Partner</a> | ';
    echo '<a href="debug_partner.php">Debug</a>';

} catch(Exception $e) {
    echo "❌ Errore: " . $e->getMessage();
    echo "<br><a href='debug_partner.php'>Controlla Debug</a>";
}
?>
