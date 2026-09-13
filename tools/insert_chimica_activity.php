<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

/**
 * Evento chimica (Molecular WebXR) — commit "evento chimica" su GitHub.
 * Il titolo contiene "chimic" così attivita_dettaglio.php usa molecularwebxr.org/app.
 */
require_once __DIR__ . '/../config.php';

$codMecc = 'BGTF010003';
$titolo = 'Laboratorio di Chimica - Molecular WebXR';
$descrizione = 'Laboratorio immersivo di chimica con simulazioni Molecular WebXR, pensato per esplorare molecole e reazioni in ambiente 3D.';
$link = 'https://molecularwebxr.org/app';
$dataOra = '2026-05-30 16:00:00';
$maxPosti = 25;
$tipo = 'laboratorio';
$durata = 60;
$supportaVr = 1;
$stato = 'pubblicata';

try {
    $stmt = $pdo->prepare('SELECT ID_Ente FROM istituti_e_partner WHERE Cod_Mecc = ? LIMIT 1');
    $stmt->execute([$codMecc]);
    $ente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ente) {
        throw new RuntimeException('Istituto BGTF010003 non trovato. Esegui prima tools/insert_fab_activity.php.');
    }

    $enteId = (int) $ente['ID_Ente'];

    $stmt = $pdo->prepare('SELECT ID_Attivita FROM attivita_eventi WHERE FK_Ente_Organizzatore = ? AND Titolo = ? LIMIT 1');
    $stmt->execute([$enteId, $titolo]);
    $att = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($att) {
        $update = $pdo->prepare('UPDATE attivita_eventi SET Descrizione = ?, Link_WebXR = ?, Data_Ora = ?, Max_Posti = ?, Tipo_Attivita = ?, Durata_Minuti = ?, Supporta_VR = ?, Stato = ? WHERE ID_Attivita = ?');
        $update->execute([$descrizione, $link, $dataOra, $maxPosti, $tipo, $durata, $supportaVr, $stato, $att['ID_Attivita']]);
        echo "Attività chimica aggiornata ID: {$att['ID_Attivita']}\n";
    } else {
        $insert = $pdo->prepare('INSERT INTO attivita_eventi (FK_Ente_Organizzatore, Titolo, Descrizione, Link_WebXR, Data_Ora, Max_Posti, Tipo_Attivita, Durata_Minuti, Supporta_VR, Materiali_URL, Stato) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insert->execute([$enteId, $titolo, $descrizione, $link, $dataOra, $maxPosti, $tipo, $durata, $supportaVr, null, $stato]);
        echo "Attività chimica inserita ID: " . $pdo->lastInsertId() . "\n";
    }
} catch (Exception $e) {
    echo 'Errore: ' . $e->getMessage() . "\n";
    exit(1);
}
