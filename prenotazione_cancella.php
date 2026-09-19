<?php
require_once 'config.php';
requireRole(['utente', 'istituto', 'partner']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metodo non consentito.');
}
requireCsrf();
$activityId = (int) ($_POST['attivita_id'] ?? 0);
$column = $_SESSION['user_type'] === 'utente' ? 'utente_id' : 'istituto_prenotante_id';
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT Data_Ora FROM attivita_eventi WHERE ID_Attivita = ? FOR UPDATE');
    $stmt->execute([$activityId]);
    $activity = $stmt->fetch();
    if (!$activity || strtotime($activity['Data_Ora']) <= time()) {
        throw new RuntimeException('La prenotazione può essere annullata solo prima dell’attività.');
    }
    $stmt = $pdo->prepare("UPDATE prenotazioni SET stato = 'cancellata' WHERE attivita_id = ? AND {$column} = ? AND stato IN ('confermata', 'in_attesa')");
    $stmt->execute([$activityId, (int) $_SESSION['user_id']]);
    $pdo->commit();
    $_SESSION['success'] = 'Prenotazione annullata.';
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($error instanceof PDOException) error_log((string) $error);
    $_SESSION['error'] = $error instanceof PDOException ? 'Impossibile annullare la prenotazione. Riprova.' : $error->getMessage();
}
header('Location: attivita_dettaglio.php?id=' . $activityId);
exit;
