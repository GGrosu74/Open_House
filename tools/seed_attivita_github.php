<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

/**
 * Importa nel DB locale le attività demo da GitHub (script in repo).
 * Esegui: php tools/seed_attivita_github.php
 */
require_once __DIR__ . '/../config.php';

$scripts = [
    'ripristina_braccio_robotico.php',
    'create_and_associate_test_3d.php',
    'tools/insert_fab_activity.php',
    'tools/insert_chimica_activity.php',
];

echo "=== Seed attività da GitHub ===\n\n";
$root = dirname(__DIR__);
$phpBin = PHP_BINARY ?: 'php';

foreach ($scripts as $script) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $script);
    echo "--- {$script} ---\n";
    if (!is_file($path)) {
        echo "SKIP: file non trovato\n\n";
        continue;
    }
    $out = [];
    $code = 0;
    exec(escapeshellarg($phpBin) . ' ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    $plain = trim(strip_tags(implode("\n", $out)));
    echo ($plain !== '' ? $plain : '(nessun output)') . "\n";
    if ($code !== 0) {
        echo "EXIT CODE: {$code}\n";
    }
    echo "\n";
}

echo "=== Attività pubblicate ===\n";
$stmt = $pdo->query("SELECT a.ID_Attivita, a.Titolo, a.Data_Ora, i.Ragione_Sociale
    FROM attivita_eventi a
    JOIN istituti_e_partner i ON a.FK_Ente_Organizzatore = i.ID_Ente
    WHERE a.Stato = 'pubblicata'
    ORDER BY a.Data_Ora ASC");
$count = 0;
foreach ($stmt as $row) {
    $count++;
    echo sprintf("#%d | %s | %s | %s\n", $row['ID_Attivita'], $row['Data_Ora'], $row['Titolo'], $row['Ragione_Sociale']);
}
echo "\nTotale: {$count} attività pubblicate.\n";
