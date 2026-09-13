<?php if (PHP_SAPI !== 'cli') { http_response_code(404); exit; } ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Registrazione</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .test-card { max-width: 1000px; margin: 0 auto; }
        .status-success { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
        table { background: white; border-radius: 8px; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="test-card">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-clipboard-check"></i> Test Registrazione Sistema</h4>
            </div>
            <div class="card-body p-4">

                <h5 class="mb-4">📋 Stato Database</h5>

                <?php
                require_once 'config.php';

                $tests = [];

                // Test 1: Connessione database
                try {
                    $result = $pdo->query("SELECT 1");
                    $tests['Connessione Database'] = ['✅', 'Connesso', 'success'];
                } catch (Exception $e) {
                    $tests['Connessione Database'] = ['❌', 'Errore: ' . $e->getMessage(), 'error'];
                }

                // Test 2: Tabella istituti_e_partner esiste
                try {
                    $result = $pdo->query("DESCRIBE istituti_e_partner");
                    $columns = [];
                    while ($row = $result->fetch()) {
                        $columns[] = $row['Field'];
                    }
                    $required = ['ID_Ente', 'Ragione_Sociale', 'Tipologia', 'Email', 'Telefono', 'password', 'Provincia', 'Regione'];
                    $missing = array_diff($required, $columns);
                    if (empty($missing)) {
                        $tests['Tabella istituti_e_partner'] = ['✅', 'Tutti i campi presenti', 'success'];
                    } else {
                        $tests['Tabella istituti_e_partner'] = ['⚠️', 'Campi mancanti: ' . implode(', ', $missing), 'warning'];
                    }
                } catch (Exception $e) {
                    $tests['Tabella istituti_e_partner'] = ['❌', 'Errore: ' . $e->getMessage(), 'error'];
                }

                // Test 3: Tabella utenti_finali esiste
                try {
                    $result = $pdo->query("DESCRIBE utenti_finali");
                    $columns = [];
                    while ($row = $result->fetch()) {
                        $columns[] = $row['Field'];
                    }
                    $required = ['id', 'nome', 'cognome', 'email', 'password', 'tipo_utente'];
                    $missing = array_diff($required, $columns);
                    if (empty($missing)) {
                        $tests['Tabella utenti_finali'] = ['✅', 'Tutti i campi presenti', 'success'];
                    } else {
                        $tests['Tabella utenti_finali'] = ['⚠️', 'Campi mancanti: ' . implode(', ', $missing), 'warning'];
                    }
                } catch (Exception $e) {
                    $tests['Tabella utenti_finali'] = ['❌', 'Errore: ' . $e->getMessage(), 'error'];
                }

                // Test 4: Conteggio record
                try {
                    $istituti = $pdo->query("SELECT COUNT(*) as cnt FROM istituti_e_partner")->fetch()['cnt'];
                    $utenti = $pdo->query("SELECT COUNT(*) as cnt FROM utenti_finali")->fetch()['cnt'];
                    $tests['Istituti nel DB'] = ['ℹ️', $istituti . ' record', 'success'];
                    $tests['Utenti nel DB'] = ['ℹ️', $utenti . ' record', 'success'];
                } catch (Exception $e) {
                    $tests['Istituti nel DB'] = ['❌', 'Errore: ' . $e->getMessage(), 'error'];
                }

                // Test 5: Funzioni PHP
                if (function_exists('hashPassword')) {
                    $tests['Funzione hashPassword'] = ['✅', 'Disponibile', 'success'];
                } else {
                    $tests['Funzione hashPassword'] = ['❌', 'Non trovata', 'error'];
                }

                if (function_exists('sanitize')) {
                    $tests['Funzione sanitize'] = ['✅', 'Disponibile', 'success'];
                } else {
                    $tests['Funzione sanitize'] = ['❌', 'Non trovata', 'error'];
                }

                // Stampa risultati
                ?>

                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Test</th>
                            <th>Status</th>
                            <th>Dettagli</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tests as $name => $result): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($name) ?></strong></td>
                                <td class="status-<?= $result[2] ?>" style="font-size: 1.2em;"><?= $result[0] ?></td>
                                <td><?= htmlspecialchars($result[1]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <hr>

                <h5 class="mb-4">🧪 Azioni di Test</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="register.php" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-person-plus"></i> Apri Form Registrazione
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="login.php" class="btn btn-secondary btn-lg w-100">
                            <i class="bi bi-box-arrow-in-right"></i> Apri Form Login
                        </a>
                    </div>
                </div>

                <hr>

                <h5 class="mb-4">📝 Istruzioni di Test</h5>

                <div class="alert alert-info">
                    <h6>Test Registrazione Istituto:</h6>
                    <ol>
                        <li>Clicca su "Apri Form Registrazione"</li>
                        <li>Seleziona "Istituto" come tipo utente</li>
                        <li>Compila i campi:
                            <ul>
                                <li><strong>Nome</strong>: Es. "IC Primaria Test"</li>
                                <li><strong>Tipo Scuola</strong>: Es. "Scuola Primaria"</li>
                                <li><strong>Indirizzo</strong>: Es. "Via Roma 123"</li>
                                <li><strong>Regione</strong>: Es. "Lombardia"</li>
                                <li><strong>Provincia</strong>: Es. "Milano"</li>
                                <li><strong>Comune</strong>: Es. "Milano"</li>
                                <li><strong>Email</strong>: Es. "test@istituto.it"</li>
                                <li><strong>Password</strong>: Minimo 8 caratteri</li>
                            </ul>
                        </li>
                        <li>Premi "Registrati"</li>
                        <li>Se vedi "Registrazione Istituto completata!" → ✅ OK</li>
                    </ol>
                </div>

                <div class="alert alert-info">
                    <h6>Test Registrazione Utente:</h6>
                    <ol>
                        <li>Clicca su "Apri Form Registrazione"</li>
                        <li>Seleziona "Utente" come tipo utente</li>
                        <li>Compila i campi:
                            <ul>
                                <li><strong>Nome</strong>: Es. "Mario"</li>
                                <li><strong>Cognome</strong>: Es. "Rossi"</li>
                                <li><strong>Tipo Utente</strong>: Es. "Studente"</li>
                                <li><strong>Email</strong>: Es. "mario@email.it"</li>
                                <li><strong>Password</strong>: Minimo 8 caratteri</li>
                            </ul>
                        </li>
                        <li>Premi "Registrati"</li>
                        <li>Se vedi "Registrazione Utente completata!" → ✅ OK</li>
                    </ol>
                </div>

                <div class="alert alert-info">
                    <h6>Test Registrazione Partner:</h6>
                    <ol>
                        <li>Clicca su "Apri Form Registrazione"</li>
                        <li>Seleziona "Partner VR/FSL" come tipo utente</li>
                        <li>Compila i campi:
                            <ul>
                                <li><strong>Nome Referente</strong>: Es. "Giovanni"</li>
                                <li><strong>Ragione Sociale</strong>: Es. "Arena VR srl"</li>
                                <li><strong>Tipologia Partner</strong>: Es. "Arena VR"</li>
                                <li><strong>Città</strong>: Es. "Milano"</li>
                                <li><strong>Regione</strong>: Es. "Lombardia"</li>
                                <li><strong>Provincia</strong>: Es. "Milano"</li>
                                <li><strong>Email</strong>: Es. "partner@arenavn.it"</li>
                                <li><strong>Password</strong>: Minimo 8 caratteri</li>
                            </ul>
                        </li>
                        <li>Premi "Registrati"</li>
                        <li>Se vedi "Registrazione Partner completata!" → ✅ OK</li>
                    </ol>
                </div>

                <hr>

                <h5 class="mb-4">🔍 Verifiche Post-Registrazione</h5>

                <div class="alert alert-success">
                    <p>Dopo la registrazione, i dati dovrebbero essere salvati nel database:</p>
                    <ul>
                        <li>✅ <strong>Istituto</strong> → tabella <code>istituti_e_partner</code> con Email salvata</li>
                        <li>✅ <strong>Utente</strong> → tabella <code>utenti_finali</code> con Email salvata</li>
                        <li>✅ <strong>Partner</strong> → tabella <code>istituti_e_partner</code> con Tipologia = ARENA_VR/AZIENDA_FSL/etc.</li>
                    </ul>
                </div>

            </div>
            <div class="card-footer text-muted">
                <small><i class="bi bi-clock"></i> Ultimo aggiornamento: <?= date('Y-m-d H:i:s') ?></small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
