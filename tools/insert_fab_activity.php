<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../config.php';

$codMecc = 'BGTF010003';
$titolo = 'Laboratorio di robototecnica - Fab Listing';
$descrizione = "Laboratorio di robototecnica organizzato da ITIS Pietro Paleocapa. Info/locandina: https://www.fab.com/listings/f5100f9e-a914-4f6c-b800-8c616be96801";
$link = 'https://www.fab.com/listings/f5100f9e-a914-4f6c-b800-8c616be96801';
$dataOra = '2026-05-28 18:00:00';
$maxPosti = 30;
$tipo = 'laboratorio';
$durata = 45;
$supportaVr = 1;
$stato = 'pubblicata';

try {
    // Find or create institute
    $stmt = $pdo->prepare("SELECT ID_Ente FROM istituti_e_partner WHERE Cod_Mecc = ? LIMIT 1");
    $stmt->execute([$codMecc]);
    $ente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ente) {
        $insert = $pdo->prepare("INSERT INTO istituti_e_partner (Cod_Mecc, Ragione_Sociale, Email, Tipologia, Indirizzo, Comune, Provincia, Regione, Stato_Validazione) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->execute([
            $codMecc,
            'ITIS PIETRO PALEOCAPA',
            $codMecc . '@istruzione.it',
            'ISTITUTO TECNICO INDUSTRIALE',
            'VIA M. GAVAZZENI 29',
            'BERGAMO',
            'BG',
            'LOMBARDIA',
            1
        ]);
        $enteId = (int)$pdo->lastInsertId();
        echo "Istituto creato con ID: $enteId\n";
    } else {
        $enteId = (int)$ente['ID_Ente'];
        echo "Istituto trovato con ID: $enteId\n";
    }

    // Check if activity exists
    $stmt = $pdo->prepare("SELECT ID_Attivita FROM attivita_eventi WHERE FK_Ente_Organizzatore = ? AND Titolo = ? LIMIT 1");
    $stmt->execute([$enteId, $titolo]);
    $att = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($att) {
        $update = $pdo->prepare("UPDATE attivita_eventi SET Descrizione = ?, Link_WebXR = ?, Data_Ora = ?, Max_Posti = ?, Tipo_Attivita = ?, Durata_Minuti = ?, Supporta_VR = ?, Materiali_URL = ?, Stato = ? WHERE ID_Attivita = ?");
        $update->execute([
            $descrizione, $link, $dataOra, $maxPosti, $tipo, $durata, $supportaVr, null, $stato, $att['ID_Attivita']
        ]);
        echo "Attività aggiornata ID: " . $att['ID_Attivita'] . "\n";
    } else {
        $insert = $pdo->prepare("INSERT INTO attivita_eventi (FK_Ente_Organizzatore, Titolo, Descrizione, Link_WebXR, Data_Ora, Max_Posti, Tipo_Attivita, Durata_Minuti, Supporta_VR, Materiali_URL, Stato) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->execute([$enteId, $titolo, $descrizione, $link, $dataOra, $maxPosti, $tipo, $durata, $supportaVr, null, $stato]);
        echo "Attività inserita con ID: " . $pdo->lastInsertId() . "\n";
    }

} catch (Exception $e) {
    echo 'Errore: ' . $e->getMessage() . "\n";
    exit(1);
}

return 0;
?>
