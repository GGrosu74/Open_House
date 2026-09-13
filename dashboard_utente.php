<?php
require_once 'config.php';
requireUtente();

$lang = $_GET['lang'] ?? 'it';
$utente_id = $_SESSION['user_id'];
$flash_success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// Ottieni prenotazioni dell'utente
$stmt = $pdo->prepare("SELECT p.*, a.ID_Attivita as attivita_id, a.Titolo as titolo, a.Data_Ora as data_ora,
                       a.Descrizione as descrizione, a.Supporta_VR as supporta_vr, a.Materiali_URL as materiali_url,
                       i.Ragione_Sociale as istituto_nome
                       FROM prenotazioni p
                       JOIN attivita_eventi a ON p.attivita_id = a.ID_Attivita
                       JOIN istituti_e_partner i ON a.FK_Ente_Organizzatore = i.ID_Ente
                       WHERE p.utente_id = ?
                       ORDER BY a.Data_Ora DESC");
$stmt->execute([$utente_id]);
$prenotazioni = $stmt->fetchAll();

// Ottieni attività disponibili
$stmt = $pdo->prepare("SELECT a.ID_Attivita as id, a.Titolo as titolo, a.Descrizione as descrizione, a.Data_Ora as data_ora,
                       a.Supporta_VR as supporta_vr, a.Max_Posti as max_partecipanti, a.Stato as stato,
                       i.Ragione_Sociale as istituto_nome,
                       COALESCE(SUM(p.numero_partecipanti),0) as prenotazioni_count
                       FROM attivita_eventi a
                       JOIN istituti_e_partner i ON a.FK_Ente_Organizzatore = i.ID_Ente
                       LEFT JOIN prenotazioni p ON a.ID_Attivita = p.attivita_id AND p.stato = 'confermata'
                       WHERE a.Stato = 'pubblicata'
                       AND a.Data_Ora > NOW()
                       GROUP BY a.ID_Attivita
                       ORDER BY a.Data_Ora ASC
                       LIMIT 10");
$stmt->execute();
$attivita_disponibili = $stmt->fetchAll();

$translations = [
    'it' => [
        'title' => 'Dashboard Utente',
        'dashboard' => 'Dashboard',
        'attivita' => 'Attività',
        'prenotazioni' => 'Le Mie Prenotazioni',
        'attivita_disponibili' => 'Attività Disponibili',
        'profilo' => 'Profilo',
        'logout' => 'Logout',
        'prenota' => 'Prenota',
        'partecipa' => 'Partecipa',
        'data_ora' => 'Data e Ora',
        'istituto' => 'Istituto',
        'stato' => 'Stato',
        'azioni' => 'Azioni',
        'nessuna_prenotazione' => 'Nessuna prenotazione',
        'vr' => 'VR'
    ],
    'en' => [
        'title' => 'User Dashboard',
        'dashboard' => 'Dashboard',
        'attivita' => 'Activities',
        'prenotazioni' => 'My Bookings',
        'attivita_disponibili' => 'Available Activities',
        'profilo' => 'Profile',
        'logout' => 'Logout',
        'prenota' => 'Book',
        'partecipa' => 'Join',
        'data_ora' => 'Date & Time',
        'istituto' => 'Institution',
        'stato' => 'Status',
        'azioni' => 'Actions',
        'nessuna_prenotazione' => 'No bookings',
        'vr' => 'VR'
    ]
];

$t = $translations[$lang];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Benvenuto, <?= htmlspecialchars($_SESSION['user_name']) ?></h2>

        <?php if ($flash_success): ?>
            <div class="alert alert-success" role="status">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flash_success) ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card shadow mb-4" id="storico-fsl">
                    <div class="card-header">
                        <h5 class="mb-0"><?= $t['prenotazioni'] ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($prenotazioni)): ?>
                            <p class="text-muted"><?= $t['nessuna_prenotazione'] ?></p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($prenotazioni as $prenotazione): ?>
                                    <div class="list-group-item">
                                        <h6><?= htmlspecialchars($prenotazione['titolo']) ?></h6>
                                        <p class="mb-1">
                                            <small class="text-muted">
                                                <?= htmlspecialchars($prenotazione['istituto_nome']) ?> -
                                                <?= date('d/m/Y H:i', strtotime($prenotazione['data_ora'])) ?>
                                            </small>
                                        </p>
                                        <span class="badge bg-<?= $prenotazione['stato'] === 'confermata' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($prenotazione['stato']) ?>
                                        </span>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($prenotazione['modalita_fruizione'] ?? 'casa') ?></span>
                                        <?php if (!empty($prenotazione['qr_code'])): ?>
                                            <span class="badge bg-dark">QR: <?= htmlspecialchars($prenotazione['qr_code']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($prenotazione['materiali_url']) && validActivityUrl($prenotazione['materiali_url'])): ?>
                                            <a href="<?= htmlspecialchars($prenotazione['materiali_url']) ?>" class="btn btn-sm btn-outline-success ms-1" target="_blank" rel="noopener noreferrer">
                                                <i class="bi bi-folder2-open"></i> Materiali
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($prenotazione['stato'] === 'confermata' && strtotime($prenotazione['data_ora']) <= time()): ?>
                                            <a href="attivita_partecipa.php?id=<?= $prenotazione['attivita_id'] ?>&lang=<?= $lang ?>" class="btn btn-sm btn-primary float-end">
                                                <i class="bi bi-box-arrow-in-right"></i> <?= $t['partecipa'] ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?= $t['attivita_disponibili'] ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($attivita_disponibili)): ?>
                            <p class="text-muted">Nessuna attività disponibile al momento</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($attivita_disponibili as $attivita): ?>
                                    <div class="list-group-item">
                                        <h6><?= htmlspecialchars($attivita['titolo']) ?></h6>
                                        <p class="mb-1">
                                            <small class="text-muted">
                                                <?= htmlspecialchars($attivita['istituto_nome']) ?> -
                                                <?= date('d/m/Y H:i', strtotime($attivita['data_ora'])) ?>
                                            </small>
                                        </p>
                                        <?php if ($attivita['supporta_vr']): ?>
                                            <span class="badge bg-info"><i class="bi bi-vr"></i> <?= $t['vr'] ?></span>
                                        <?php endif; ?>
                                        <span class="badge bg-secondary"><?= $attivita['prenotazioni_count'] ?>/<?= $attivita['max_partecipanti'] ?> posti</span>
                                        <a href="attivita_dettaglio.php?id=<?= $attivita['id'] ?>&lang=<?= $lang ?>#prenotazione" class="btn btn-sm btn-primary float-end">
                                            <?= $t['prenota'] ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
