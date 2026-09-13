<?php if (PHP_SAPI !== 'cli') { http_response_code(404); exit; } ?>
<?php
/**
 * Script di migrazione: istituti -> istituti_e_partner
 *
 * Da eseguire UNA SOLA VOLTA per aggiornare la struttura del database
 *
 * Uso:
 * 1. Posizionare il file in una directory accessibile (es. /admin/)
 * 2. Accedere via browser: http://localhost/Open_House/migrate_istituti.php
 * 3. Fare click su "Esegui Migrazione"
 * 4. Verificare i risultati
 * 5. Eliminare questo file dopo il completamento
 */

require_once 'config.php';

$success = false;
$error = '';
$message = '';
$backup_created = false;
$migration_completed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_migration'])) {
    try {
        $pdo->beginTransaction();

        // Step 1: Crea la tabella di backup se esiste la vecchia tabella
        $checkTable = $pdo->query("SHOW TABLES LIKE 'istituti'");
        if ($checkTable && $checkTable->rowCount() > 0) {
            // Backup della tabella vecchia
            $pdo->exec("CREATE TABLE IF NOT EXISTS istituti_backup_legacy AS SELECT * FROM istituti");
            $backup_created = true;
            $message .= "✓ Backup della tabella 'istituti' creato in 'istituti_backup_legacy'\n";
        }

        // Step 2: Verifica se la nuova tabella esiste già
        $checkNewTable = $pdo->query("SHOW TABLES LIKE 'istituti_e_partner'");
        $newTableExists = $checkNewTable && $checkNewTable->rowCount() > 0;

        if (!$newTableExists) {
            // Crea la nuova tabella se non esiste
            $createTableSQL = <<<SQL
            CREATE TABLE `istituti_e_partner` (
              `ID_Ente` int(11) NOT NULL AUTO_INCREMENT,
              `Ragione_Sociale` varchar(255) DEFAULT NULL,
              `Tipologia` varchar(150) DEFAULT NULL,
              `CF_PIVA` varchar(20) DEFAULT NULL,
              `Cod_Mecc` varchar(20) DEFAULT NULL,
              `Cod_REA` varchar(20) DEFAULT NULL,
              `Indirizzo` varchar(255) DEFAULT NULL,
              `Comune` varchar(150) DEFAULT NULL,
              `Provincia` varchar(10) DEFAULT NULL,
              `Regione` varchar(100) DEFAULT NULL,
              `Coordinate_GPS` varchar(100) DEFAULT NULL,
              `Email` varchar(255) DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT current_timestamp(),
              `Stato_Validazione` tinyint(1) DEFAULT 0 COMMENT '0=In attesa, 1=Approvato, 2=Bloccato',
              PRIMARY KEY (`ID_Ente`),
              UNIQUE KEY `cod_mecc` (`Cod_Mecc`),
              KEY `idx_ragione_sociale` (`Ragione_Sociale`),
              KEY `idx_provincia` (`Provincia`),
              KEY `idx_comune` (`Comune`),
              KEY `idx_tipologia` (`Tipologia`),
              KEY `idx_stato_validazione` (`Stato_Validazione`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL;

            $pdo->exec($createTableSQL);
            $message .= "✓ Tabella 'istituti_e_partner' creata\n";
        } else {
            $message .= "✓ Tabella 'istituti_e_partner' esiste già\n";
        }

        // Step 3: Importa dati dalla vecchia tabella se esiste
        if ($backup_created || $newTableExists) {
            $pdo->exec("
                INSERT INTO istituti_e_partner
                  (ID_Ente, Cod_Mecc, Ragione_Sociale, Email, Tipologia, Indirizzo, Comune, Provincia, Regione, created_at, Stato_Validazione)
                SELECT
                  id, codice_istituto, nome, email, tipo_scuola, indirizzo, comune, provincia, regione, created_at, 1
                FROM istituti
                ON DUPLICATE KEY UPDATE
                  Ragione_Sociale = VALUES(Ragione_Sociale),
                  Tipologia = VALUES(Tipologia),
                  Email = VALUES(Email)
            ");
            $message .= "✓ Dati migrati dalla tabella 'istituti' a 'istituti_e_partner'\n";
        }

        // Step 4: Rinomina la vecchia tabella come backup
        if ($backup_created && !$newTableExists) {
            // Solo se abbiamo fatto il backup e la nuova non esisteva
            // Altrimenti manteniamo la vecchia come è
        }

        $pdo->commit();
        $migration_completed = true;
        $success = true;
        $message .= "\n✓✓✓ MIGRAZIONE COMPLETATA CON SUCCESSO ✓✓✓\n\n";
        $message .= "Riepilogo:\n";
        $message .= "- Backup creato: " . ($backup_created ? "SI" : "NO") . "\n";
        $message .= "- Dati migrati: Consultare il database per verificare\n";
        $message .= "- Tabella nuova: istituti_e_partner (attiva)\n";
        $message .= "- Tabella vecchia: istituti (backup salvato come istituti_backup_legacy)\n\n";
        $message .= "PROSSIMI PASSI:\n";
        $message .= "1. Verificare i dati nel database (phpmyadmin o client MySQL)\n";
        $message .= "2. Eseguire test sul sito\n";
        $message .= "3. Se tutto funziona, eliminare il file di migrazione\n";
        $message .= "4. Eventualmente eliminare la tabella 'istituti' se non più necessaria\n";

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "ERRORE DURANTE LA MIGRAZIONE:\n";
        $success = false;
    }
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrazione Database - istituti_e_partner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem 0; }
        .container { max-width: 700px; }
        .card { border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2); border-radius: 10px; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .alert { border: none; border-radius: 8px; }
        .alert-info { background-color: #e7f3ff; color: #004085; border-left: 4px solid #0066cc; }
        .alert-success { background-color: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #5568d3 0%, #653a8a 100%); }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header">
            <h1 class="mb-0">🔄 Migrazione Database</h1>
            <small>istituti → istituti_e_partner</small>
        </div>
        <div class="card-body p-4">

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <h4 class="alert-heading">✓ Migrazione Completata!</h4>
                    <pre><?= htmlspecialchars($message) ?></pre>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger">
                    <h4 class="alert-heading">✗ Errore</h4>
                    <pre><?= htmlspecialchars($error) ?></pre>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <strong>⚠️ ATTENZIONE:</strong> Questa migrazione modificherà la struttura del database.
                    <ul class="mb-0 mt-2">
                        <li>Crea backup della tabella 'istituti' come 'istituti_backup_legacy'</li>
                        <li>Crea la nuova tabella 'istituti_e_partner' con struttura aggiornata</li>
                        <li>Migra i dati dalla vecchia alla nuova tabella</li>
                    </ul>
                </div>

                <h5 class="mt-4 mb-3">📋 Modifiche che verranno applicate:</h5>
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Vecchio Nome</th>
                            <th>Nuovo Nome</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>id</td><td>ID_Ente (PRIMARY KEY)</td></tr>
                        <tr><td>codice_istituto</td><td>Cod_Mecc</td></tr>
                        <tr><td>nome</td><td>Ragione_Sociale</td></tr>
                        <tr><td>tipo_scuola</td><td>Tipologia</td></tr>
                        <tr><td>email</td><td>Email</td></tr>
                        <tr><td>indirizzo</td><td>Indirizzo</td></tr>
                        <tr><td>comune</td><td>Comune</td></tr>
                        <tr><td>provincia</td><td>Provincia</td></tr>
                        <tr><td>regione</td><td>Regione</td></tr>
                        <tr class="table-success"><td colspan="2"><strong>Nuove colonne:</strong> CF_PIVA, Cod_REA, Coordinate_GPS, Stato_Validazione</td></tr>
                    </tbody>
                </table>

                <form method="POST" class="mt-4">
<?= csrfField() ?>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirm" name="confirm_migration" required>
                        <label class="form-check-label" for="confirm">
                            Confermo di aver fatto un backup e autorizzo l'esecuzione della migrazione
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-play-fill"></i> Esegui Migrazione
                    </button>
                </form>
            <?php endif; ?>

        </div>
        <div class="card-footer text-muted small text-center">
            <p class="mb-0">Per informazioni: consultare MIGRATION_LOG.md</p>
            <p class="mb-0">Migrazione script - Open House VR 2026</p>
        </div>
    </div>
</div>
</body>
</html>
