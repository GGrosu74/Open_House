<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../config.php';
$link = '%fab.com/listings/f5100f9e%';
try {
    $stmt = $pdo->prepare("SELECT a.ID_Attivita, a.Titolo, a.Data_Ora, i.Ragione_Sociale, a.Link_WebXR FROM attivita_eventi a JOIN istituti_e_partner i ON a.FK_Ente_Organizzatore=i.ID_Ente WHERE a.Link_WebXR LIKE ? LIMIT 10");
    $stmt->execute([$link]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        echo "Nessuna attività trovata con link Fab.\n";
        exit(0);
    }
    foreach ($rows as $r) {
        echo "ID: {$r['ID_Attivita']} | Titolo: {$r['Titolo']} | Data: {$r['Data_Ora']} | Istituto: {$r['Ragione_Sociale']} | Link: {$r['Link_WebXR']}\n";
    }
} catch (Exception $e) {
    echo 'Errore: ' . $e->getMessage() . "\n";
    exit(1);
}
?>
