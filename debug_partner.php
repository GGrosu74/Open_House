<?php if (PHP_SAPI !== 'cli') { http_response_code(404); exit; } ?>
<?php
require_once 'config.php';

echo "<h2>Debug Partner Database</h2>";

// Controlla tutti i partner (indipendentemente dallo stato)
echo "<h3>Tutti i partner nel database:</h3>";
try {
    $stmt = $pdo->query("SELECT ID_Ente, Ragione_Sociale, Tipologia, Cod_REA, Stato_Validazione FROM istituti_e_partner WHERE Tipologia IN ('AZIENDA', 'ARENA_VR', 'PARTNER_VR') ORDER BY ID_Ente DESC LIMIT 20");
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($partners)) {
        echo "❌ Nessun partner trovato nel database<br>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Tipologia</th><th>Cod_REA</th><th>Stato</th></tr>";
        foreach ($partners as $p) {
            $stato = $p['Stato_Validazione'] == 1 ? '✓ Approvato' : '✗ Non approvato (' . $p['Stato_Validazione'] . ')';
            echo "<tr><td>{$p['ID_Ente']}</td><td>{$p['Ragione_Sociale']}</td><td>{$p['Tipologia']}</td><td>{$p['Cod_REA']}</td><td>{$stato}</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Errore: " . $e->getMessage();
}

echo "<br><br>";

// Controlla Partner VR approvati
echo "<h3>Partner VR approvati (visibili):</h3>";
try {
    $stmt = $pdo->query("SELECT ID_Ente, Ragione_Sociale FROM istituti_e_partner WHERE Tipologia IN ('ARENA_VR', 'PARTNER_VR') AND Stato_Validazione = 1");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo count($results) . " trovati<br>";
    foreach ($results as $r) {
        echo "- {$r['Ragione_Sociale']}<br>";
    }
} catch (Exception $e) {
    echo "Errore: " . $e->getMessage();
}

echo "<br>";

// Controlla Partner FSL approvati
echo "<h3>Partner FSL approvati (visibili):</h3>";
try {
    $stmt = $pdo->query("SELECT ID_Ente, Ragione_Sociale FROM istituti_e_partner WHERE Cod_REA IS NOT NULL AND Cod_REA != '' AND Stato_Validazione = 1");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo count($results) . " trovati<br>";
    foreach ($results as $r) {
        echo "- {$r['Ragione_Sociale']}<br>";
    }
} catch (Exception $e) {
    echo "Errore: " . $e->getMessage();
}

echo "<br><br>";
echo '<a href="partner_istituti.php">← Torna a Partner</a>';
?>
