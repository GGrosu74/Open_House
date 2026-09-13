<?php if (PHP_SAPI !== 'cli') { http_response_code(404); exit; } ?>
<?php
require_once 'config.php';

$graphitiUrl = 'https://www.virtualtour-360.it/vt/graphiti-h1d2/vt-web/';
$attivitaTitolo = 'Tour Virtuale Graphiti';

$partnerId = isset($_GET['partner_id']) ? (int)$_GET['partner_id'] : 0;

try {
    if ($partnerId === 0) {
        $stmt = $pdo->prepare("SELECT ID_Ente FROM istituti_e_partner WHERE Ragione_Sociale = ? AND Stato_Validazione = 1 LIMIT 1");
        $stmt->execute(['Prova Robototecnica']);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) {
            echo "❌ Partner 'Prova Robototecnica' non trovato. Passa ?partner_id=ID_Ente nell'URL.<br>";
            echo "<a href=\"debug_partner.php\">Debug partner</a>";
            exit;
        }
        $partnerId = (int) $p['ID_Ente'];
        echo "Partner Prova Robototecnica ID: {$partnerId}<br>";
    } else {
        echo "Partner selezionato ID: $partnerId<br>";
    }

    // Controlla se l'attività esiste già per quel partner
    $stmt = $pdo->prepare("SELECT ID_Attivita FROM attivita_eventi WHERE FK_Ente_Organizzatore = ? AND Titolo = ? LIMIT 1");
    $stmt->execute([$partnerId, $attivitaTitolo]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    $dataOra = '2026-06-01 10:00:00';

    if (!$existing) {
        $stmt = $pdo->prepare("INSERT INTO attivita_eventi (FK_Ente_Organizzatore, Titolo, Descrizione, Link_WebXR, Data_Ora, Max_Posti, Flag_FSL, Tipo_Attivita, Durata_Minuti, Supporta_VR, Materiali_URL, Stato) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $partnerId,
            $attivitaTitolo,
            'Tour virtuale 360° fornito da Graphiti (virtual tour embed).',
            $graphitiUrl,
            $dataOra,
            50,
            0,
            'tour_virtuale',
            30,
            1,
            null,
            'pubblicata'
        ]);
        echo "✓ Attivita '$attivitaTitolo' creata per partner ID $partnerId.<br>";
    } else {
        $stmt = $pdo->prepare("UPDATE attivita_eventi SET Descrizione = ?, Link_WebXR = ?, Data_Ora = ?, Max_Posti = ?, Flag_FSL = ?, Tipo_Attivita = ?, Durata_Minuti = ?, Supporta_VR = ?, Materiali_URL = ?, Stato = ? WHERE ID_Attivita = ?");
        $stmt->execute([
            'Tour virtuale 360° fornito da Graphiti (virtual tour embed).',
            $graphitiUrl,
            $dataOra,
            50,
            0,
            'tour_virtuale',
            30,
            1,
            null,
            'pubblicata',
            $existing['ID_Attivita']
        ]);
        echo "✓ Attivita aggiornata per partner ID $partnerId (ID attivita: {$existing['ID_Attivita']}).<br>";
    }

    echo "<br><a href=\"debug_attivita.php\">Apri debug_attivita</a><br>";
    echo "<a href=\"attivita_elenco.php?lang=it\">Vai a elenco attività</a>";
} catch (Exception $e) {
    echo "❌ Errore: " . $e->getMessage();
}
?>
