<?php if (PHP_SAPI !== 'cli') { http_response_code(404); exit; } ?>
<?php
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $codMecc = 'BGTF010003';
    $titoloVecchio = 'Simulazione WebXR - Braccio robotico 3D';
    $titolo = 'Insect Robo';
    $descrizione = 'Laboratorio di robototecnica con simulazione WebXR di Insect Robo, un braccio robotico 3D ispirato agli insetti, pensato per utenti registrati del portale e per l\'orientamento tecnologico.';
    $linkWebxr = 'https://Novia-RDI-XR-Robotics.github.io/a-frame-xr-tutorial/';
    $dataOra = '2026-05-15 10:00:00';

    $stmt = $pdo->prepare("SELECT ID_Ente FROM istituti_e_partner WHERE Cod_Mecc = ? LIMIT 1");
    $stmt->execute([$codMecc]);
    $ente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ente) {
        $stmt = $pdo->prepare("INSERT INTO istituti_e_partner (Cod_Mecc, Ragione_Sociale, Email, Tipologia, Indirizzo, Comune, Provincia, Regione, Stato_Validazione) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $codMecc,
            '"PIETRO PALEOCAPA"',
            'BGTF010003@istruzione.it',
            'ISTITUTO TECNICO INDUSTRIALE',
            'VIA M. GAVAZZENI 29',
            'BERGAMO',
            'BG',
            'LOMBARDIA',
            1
        ]);

        $enteId = (int)$pdo->lastInsertId();
        echo "✓ Istituto creato: {$codMecc} - PIETRO PALEOCAPA (ID: {$enteId})<br>";
    } else {
        $enteId = (int)$ente['ID_Ente'];
    }

    $stmt = $pdo->prepare("SELECT ID_Attivita, Titolo FROM attivita_eventi WHERE FK_Ente_Organizzatore = ? AND Titolo IN (?, ?) LIMIT 1");
    $stmt->execute([$enteId, $titoloVecchio, $titolo]);
    $esistente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($esistente) {
        $stmt = $pdo->prepare("UPDATE attivita_eventi SET Titolo = ?, Descrizione = ?, Link_WebXR = ?, Data_Ora = ?, Max_Posti = ?, Flag_FSL = ?, Tipo_Attivita = ?, Durata_Minuti = ?, Supporta_VR = ?, Materiali_URL = ?, Stato = ? WHERE ID_Attivita = ?");
        $stmt->execute([
            $titolo,
            $descrizione,
            $linkWebxr,
            $dataOra,
            30,
            1,
            'laboratorio',
            45,
            1,
            null,
            'pubblicata',
            $esistente['ID_Attivita']
        ]);

        echo "✓ Attività già presente e aggiornata: {$titolo}<br>";
        echo "ID attività: " . (int)$esistente['ID_Attivita'] . "<br>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO attivita_eventi (FK_Ente_Organizzatore, Titolo, Descrizione, Link_WebXR, Data_Ora, Max_Posti, Flag_FSL, Tipo_Attivita, Durata_Minuti, Supporta_VR, Materiali_URL, Stato) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $enteId,
            $titolo,
            $descrizione,
            $linkWebxr,
            $dataOra,
            30,
            1,
            'laboratorio',
            45,
            1,
            null,
            'pubblicata'
        ]);

        echo "✓ Attività inserita con successo: {$titolo}<br>";
        echo "ID attività: " . (int)$pdo->lastInsertId() . "<br>";
    }

    echo "<br><a href=\"attivita_elenco.php?lang=it\">Vai a elenco attività</a>";
} catch (Exception $e) {
    http_response_code(500);
    echo "❌ Errore: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
?>
