<?php
require_once 'config.php';
requireRole(['istituto', 'partner']);

$lang = $_GET['lang'] ?? 'it';
$istituto_id = $_SESSION['user_id'];
$flash_success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

$stmt = $pdo->prepare("SELECT a.ID_Attivita as id, a.Titolo as titolo, a.Descrizione as descrizione, a.Data_Ora as data_ora,
                       a.Max_Posti as max_partecipanti, a.Stato as stato, COALESCE(SUM(p.numero_partecipanti),0) as prenotazioni_count
                       FROM attivita_eventi a
                       LEFT JOIN prenotazioni p ON a.ID_Attivita = p.attivita_id AND p.stato = 'confermata'
                       WHERE a.FK_Ente_Organizzatore = ?
                       GROUP BY a.ID_Attivita
                       ORDER BY a.Data_Ora DESC");
$stmt->execute([$istituto_id]);
$attivita = $stmt->fetchAll();

$translations = [
    'it' => ['title' => 'Gestisci Attività', 'nuova' => 'Nuova Attività', 'titolo' => 'Titolo', 'data_ora' => 'Data e Ora', 'stato' => 'Stato', 'prenotazioni' => 'Prenotazioni', 'azioni' => 'Azioni', 'modifica' => 'Modifica', 'elimina' => 'Elimina'],
    'en' => ['title' => 'Manage Activities', 'nuova' => 'New Activity', 'titolo' => 'Title', 'data_ora' => 'Date & Time', 'stato' => 'Status', 'prenotazioni' => 'Bookings', 'azioni' => 'Actions', 'modifica' => 'Edit', 'elimina' => 'Delete']
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
        <?php if ($flash_success): ?>
            <div class="alert alert-success" role="status"><?= htmlspecialchars($flash_success) ?></div>
        <?php endif; ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?= $t['title'] ?></h2>
            <a href="attivita_nuova.php?lang=<?= $lang ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> <?= $t['nuova'] ?>
            </a>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <?php if (empty($attivita)): ?>
                    <p class="text-muted">Nessuna attività. <a href="attivita_nuova.php?lang=<?= $lang ?>">Crea la prima</a></p>
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
                                <?php foreach ($attivita as $a): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($a['titolo']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($a['data_ora'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $a['stato'] === 'pubblicata' ? 'success' : ($a['stato'] === 'in_corso' ? 'warning' : 'secondary') ?>">
                                                <?= $a['stato'] === 'bozza' ? 'Da approvare' : ($a['stato'] === 'cancellata' ? 'Bloccata' : ucfirst($a['stato'])) ?>
                                            </span>
                                        </td>
                                        <td><?= $a['prenotazioni_count'] ?>/<?= $a['max_partecipanti'] ?></td>
                                        <td>
                                            <a href="attivita_modifica.php?id=<?= $a['id'] ?>&lang=<?= $lang ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i> <?= $t['modifica'] ?>
                                            </a>
                                            <a href="attivita_dettaglio.php?id=<?= $a['id'] ?>&lang=<?= $lang ?>" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i>
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
