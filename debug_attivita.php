<?php if (PHP_SAPI !== 'cli') { http_response_code(404); exit; } ?>
<?php
require_once 'config.php';

echo "<h2>Debug Attivita</h2>";

echo "<h3>Attivita_eventi (ultime 100) con verifica Ente Organizzatore</h3>";
try {
    $sql = "SELECT a.ID_Attivita, a.Titolo, a.Data_Ora, a.Stato, a.FK_Ente_Organizzatore, a.Supporta_VR, a.Link_WebXR,
                   i.ID_Ente AS istituto_id, i.Ragione_Sociale AS istituto_nome, i.Stato_Validazione
            FROM attivita_eventi a
            LEFT JOIN istituti_e_partner i ON a.FK_Ente_Organizzatore = i.ID_Ente
            ORDER BY a.ID_Attivita DESC LIMIT 100";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        echo "❌ Nessuna attivita trovata in attivita_eventi<br>";
    } else {
        echo "<table border='1' cellpadding='6'>";
        echo "<tr><th>ID</th><th>Titolo</th><th>Data_Ora</th><th>Stato</th><th>FK_Ente</th><th>Istituto</th><th>Validato</th><th>VR</th><th>Link</th></tr>";
        foreach ($rows as $r) {
            $istituto_display = $r['istituto_id'] ? htmlspecialchars($r['istituto_nome']) . " (ID {$r['istituto_id']})" : '<span style="color:red">MANCANTE/ID ' . htmlspecialchars($r['FK_Ente_Organizzatore']) . '</span>';
            $validato = $r['Stato_Validazione'] ?? 'N/A';
            echo "<tr>";
            echo "<td>{$r['ID_Attivita']}</td>";
            echo "<td>" . htmlspecialchars($r['Titolo']) . "</td>";
            echo "<td>{$r['Data_Ora']}</td>";
            echo "<td>{$r['Stato']}</td>";
            echo "<td>" . htmlspecialchars($r['FK_Ente_Organizzatore']) . "</td>";
            echo "<td>$istituto_display</td>";
            echo "<td>" . htmlspecialchars($validato) . "</td>";
            echo "<td>" . htmlspecialchars($r['Supporta_VR']) . "</td>";
            echo "<td>" . htmlspecialchars($r['Link_WebXR'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Errore: " . $e->getMessage();
}

echo "<h3>Riepilogo per Stato</h3>";
try {
    $stmt = $pdo->query("SELECT Stato, COUNT(*) AS cnt FROM attivita_eventi GROUP BY Stato");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        foreach ($rows as $r) {
            echo htmlspecialchars($r['Stato']) . ": " . $r['cnt'] . "<br>";
        }
    } else {
        echo "Nessun dato di riepilogo trovato.<br>";
    }
} catch (Exception $e) {
    echo "Errore riepilogo: " . $e->getMessage();
}

echo "<br><a href=\"attivita_elenco.php?lang=it\">Torna a elenco</a>";
?>
