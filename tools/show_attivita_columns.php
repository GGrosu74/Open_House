<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../config.php';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM attivita_eventi");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in attivita_eventi:<br><ul>";
    foreach ($cols as $c) {
        echo '<li>' . htmlspecialchars($c['Field']) . ' - ' . htmlspecialchars($c['Type']) . '</li>';
    }
    echo '</ul>';
} catch (Exception $e) {
    echo 'Errore: ' . $e->getMessage();
}
?>
