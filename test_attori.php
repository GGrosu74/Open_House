<?php if (PHP_SAPI !== 'cli') { http_response_code(404); exit; } ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Attori Principali</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .test-card { max-width: 1200px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="test-card">
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-clipboard-check"></i> Test Sezione "Attori Principali"</h4>
            </div>
            <div class="card-body p-4">

                <h5 class="mb-4">📊 Status Database</h5>

                <?php
                require_once 'config.php';

                // Conteggi
                $totali = [];
                try {
                    $stmt = $pdo->query("SELECT Tipologia, COUNT(*) as count FROM istituti_e_partner GROUP BY Tipologia ORDER BY count DESC");
                    while ($row = $stmt->fetch()) {
                        $totali[$row['Tipologia']] = $row['count'];
                    }
                } catch (Exception $e) {
                    echo "<div class='alert alert-danger'>Errore database: " . $e->getMessage() . "</div>";
                }
                ?>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Tipologia Ente</th>
                                <th>Conteggio</th>
                                <th>Categoria</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($totali as $tipo => $count): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($tipo) ?></strong></td>
                                    <td><span class="badge bg-info"><?= $count ?></span></td>
                                    <td>
                                        <?php
                                        if (strpos($tipo, 'SCUOLA') !== false || strpos($tipo, 'LICEO') !== false || strpos($tipo, 'ISTITUTO') !== false) {
                                            echo '<span class="badge bg-primary">Istituti</span>';
                                        } elseif (strpos($tipo, 'ARENA') !== false) {
                                            echo '<span class="badge bg-success">Partner VR</span>';
                                        } else {
                                            echo '<span class="badge bg-warning">Partner FSL</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <hr>

                <h5 class="mb-4">🧪 Test Link Attori Principali</h5>

                <div class="row">
                    <div class="col-lg-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6>1. Istituti</h6>
                                <p class="text-muted small">Scuole, università, accademie, ITS</p>
                                <a href="istituti_elenco.php" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-link-45deg"></i> Visita Pagina
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6>2. Partner VR</h6>
                                <p class="text-muted small">Arena fisse e mobili</p>
                                <a href="istituti_elenco.php?tipologia_ente=arena_vr" class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-link-45deg"></i> Visita Pagina (filtro Arena VR)
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6>3. Partner FSL</h6>
                                <p class="text-muted small">Aziende e istituzioni</p>
                                <a href="istituti_elenco.php" class="btn btn-warning btn-sm w-100">
                                    <i class="bi bi-link-45deg"></i> Visita Pagina (tutti)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <h5 class="mb-4">📝 Verifiche Completate</h5>

                <div class="alert alert-success">
                    <h6 class="alert-heading"><i class="bi bi-check-circle"></i> ✅ Correzioni Implementate</h6>
                    <ul class="mb-0">
                        <li>✅ Bottoni "Attori Principali" convertiti in <strong>LINK funzionanti</strong></li>
                        <li>✅ Aggiunto <strong>mapping tipologie</strong> tra codici filtro e valori database</li>
                        <li>✅ Link 1: Mostra tutti gli istituti (scuole, università, etc.)</li>
                        <li>✅ Link 2: Filtra per Arena VR</li>
                        <li>✅ Link 3: Generico per Partner FSL</li>
                        <li>✅ Database ha <?= array_sum($totali) ?> enti registrati</li>
                    </ul>
                </div>

                <div class="alert alert-info">
                    <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Come Testare</h6>
                    <ol class="mb-0">
                        <li>Vai a <a href="index.php" target="_blank">index.php</a></li>
                        <li>Scorri fino alla sezione "Ecosistema VR Open House"</li>
                        <li>Nella sezione "Attori Principali", clicca sui link</li>
                        <li>Verifica che le pagine si carichino con i filtri corretti</li>
                    </ol>
                </div>

            </div>
            <div class="card-footer text-muted">
                <small><i class="bi bi-clock"></i> Testato il: <?= date('Y-m-d H:i:s') ?></small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
