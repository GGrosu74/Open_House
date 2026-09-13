<?php
require_once 'config.php';
header('Content-Type: application/json');

$attivita_id = intval($_GET['attivita_id'] ?? 0);
requireActivityAccess($pdo,$attivita_id);
$userTable=getUserTable($pdo);
$result = [];

try {
    if ($attivita_id > 0) {
        $stmt = $pdo->prepare("SELECT m.*, u.nome as utente_nome, i.Ragione_Sociale as istituto_nome
                               FROM messaggi_chat m
                               LEFT JOIN {$userTable} u ON m.utente_id = u.id
                               LEFT JOIN istituti_e_partner i ON m.istituto_id = i.ID_Ente
                               WHERE m.attivita_id = ?
                               ORDER BY m.created_at ASC");
        $stmt->execute([$attivita_id]);
        $messaggi = $stmt->fetchAll();

        foreach ($messaggi as $msg) {
            $result[] = [
                'nome' => htmlspecialchars($msg['utente_nome'] ?: $msg['istituto_nome'] ?: 'Anonimo'),
                'messaggio' => htmlspecialchars($msg['messaggio']),
                'time' => date('H:i', strtotime($msg['created_at']))
            ];
        }
    }
} catch(PDOException $e) {
    // In caso di errore, ritorna array vuoto
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Chat error: " . $e->getMessage());
    }
}

echo json_encode($result);
?>
