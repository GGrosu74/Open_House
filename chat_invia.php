<?php
require_once 'config.php';
requireLogin();

$attivita_id=0;
$sent=false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attivita_id = intval($_POST['attivita_id'] ?? 0);
    requireActivityAccess($pdo,$attivita_id);
    $messaggio = sanitize($_POST['messaggio'] ?? '');

    if (!empty($messaggio) && mb_strlen($messaggio)<=2000 && $attivita_id > 0) {
        $user_id = $_SESSION['user_id'];
        $user_type = $_SESSION['user_type'];

        if (in_array($user_type,['istituto','partner'],true)) {
            $stmt = $pdo->prepare("INSERT INTO messaggi_chat (attivita_id, istituto_id, messaggio) VALUES (?, ?, ?)");
            $stmt->execute([$attivita_id, $user_id, $messaggio]);
            $sent=true;
        } elseif ($user_type==='utente') {
            $stmt = $pdo->prepare("INSERT INTO messaggi_chat (attivita_id, utente_id, messaggio) VALUES (?, ?, ?)");
            $stmt->execute([$attivita_id, $user_id, $messaggio]);
            $sent=true;
        }
    }
}

if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $sent]);
    exit;
}

header('Location: attivita_partecipa.php?id=' . $attivita_id);
exit;
?>
