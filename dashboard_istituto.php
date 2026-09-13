<?php
require_once 'config.php';
requireIstituto();

$lang = $_GET['lang'] ?? 'it';
$istituto_id = $_SESSION['user_id'];

// Ottieni statistiche
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attivita_eventi WHERE FK_Ente_Organizzatore = ?");
$stmt->execute([$istituto_id]);
$total_attivita = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM prenotazioni p
                       JOIN attivita_eventi a ON p.attivita_id = a.ID_Attivita
                       WHERE a.FK_Ente_Organizzatore = ?");
$stmt->execute([$istituto_id]);
$total_prenotazioni = $stmt->fetch()['total'];

// Ottieni attività recenti
$stmt = $pdo->prepare("SELECT a.ID_Attivita as id, a.Titolo as titolo, a.Descrizione as descrizione, a.Data_Ora as data_ora,
                       a.Max_Posti as max_partecipanti, a.Stato as stato, a.created_at,
                       COALESCE(SUM(p.numero_partecipanti),0) as prenotazioni_count
                       FROM attivita_eventi a
                       LEFT JOIN prenotazioni p ON a.ID_Attivita = p.attivita_id AND p.stato = 'confermata'
                       WHERE a.FK_Ente_Organizzatore = ?
                       GROUP BY a.ID_Attivita
                       ORDER BY a.created_at DESC
                       LIMIT 5");
$stmt->execute([$istituto_id]);
$attivita_recenti = $stmt->fetchAll();

$translations = [
    'it' => [
        'title' => 'Dashboard Istituto',
        'dashboard' => 'Dashboard',
        'attivita' => 'Attività',
        'prenotazioni' => 'Prenotazioni',
        'nuova_attivita' => 'Nuova Attività',
        'gestisci_attivita' => 'Gestisci Attività',
        'profilo' => 'Profilo',
        'logout' => 'Logout',
        'totale_attivita' => 'Totale Attività',
        'totale_prenotazioni' => 'Totale Prenotazioni',
        'attivita_recenti' => 'Attività Recenti',
        'titolo' => 'Titolo',
        'data_ora' => 'Data e Ora',
        'stato' => 'Stato',
        'prenotazioni' => 'Prenotazioni',
        'azioni' => 'Azioni',
        'modifica' => 'Modifica',
        'visualizza' => 'Visualizza'
    ],
    'en' => [
        'title' => 'Institution Dashboard',
        'dashboard' => 'Dashboard',
        'attivita' => 'Activities',
        'prenotazioni' => 'Bookings',
        'nuova_attivita' => 'New Activity',
        'gestisci_attivita' => 'Manage Activities',
        'profilo' => 'Profile',
        'logout' => 'Logout',
        'totale_attivita' => 'Total Activities',
        'totale_prenotazioni' => 'Total Bookings',
        'attivita_recenti' => 'Recent Activities',
        'titolo' => 'Title',
        'data_ora' => 'Date & Time',
        'stato' => 'Status',
        'prenotazioni' => 'Bookings',
        'azioni' => 'Actions',
        'modifica' => 'Edit',
        'visualizza' => 'View'
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
        <div class="row mb-4">
            <div class="col-md-6">
                <h2>Benvenuto, <?= htmlspecialchars($_SESSION['user_name']) ?></h2>
            </div>
            <div class="col-md-6 text-end">
                <a href="attivita_nuova.php?lang=<?= $lang ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> <?= $t['nuova_attivita'] ?>
                </a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title"><?= $t['totale_attivita'] ?></h5>
                        <h2 class="mb-0"><?= $total_attivita ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title"><?= $t['totale_prenotazioni'] ?></h5>
                        <h2 class="mb-0"><?= $total_prenotazioni ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0"><?= $t['attivita_recenti'] ?></h5>
            </div>
            <div class="card-body">
                <?php if (empty($attivita_recenti)): ?>
                    <p class="text-muted">Nessuna attività ancora. <a href="attivita_nuova.php?lang=<?= $lang ?>">Crea la prima attività</a></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><?= $t['titolo'] ?></th>
                                    <th><?= $t['data_ora'] ?></th>
                                    <th><?= $t['stato'] ?></th>
                                    <th><?= $t['prenotazioni'] ?></th>
                                    <th><?= $t['azioni'] ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attivita_recenti as $attivita): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($attivita['titolo']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($attivita['data_ora'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $attivita['stato'] === 'pubblicata' ? 'success' : ($attivita['stato'] === 'in_corso' ? 'warning' : 'secondary') ?>">
                                                <?= $attivita['stato'] === 'bozza' ? 'Da approvare' : ($attivita['stato'] === 'cancellata' ? 'Bloccata' : ucfirst($attivita['stato'])) ?>
                                            </span>
                                        </td>
                                        <td><?= $attivita['prenotazioni_count'] ?></td>
                                        <td>
                                            <a href="attivita_modifica.php?id=<?= $attivita['id'] ?>&lang=<?= $lang ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i> <?= $t['modifica'] ?>
                                            </a>
                                            <a href="attivita_dettaglio.php?id=<?= $attivita['id'] ?>&lang=<?= $lang ?>" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i> <?= $t['visualizza'] ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
