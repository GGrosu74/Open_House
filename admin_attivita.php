<?php
require_once 'config.php';
requireAdmin();

$lang = $_GET['lang'] ?? $_POST['lang'] ?? 'it';
$allowedStatuses = ['bozza', 'pubblicata', 'cancellata'];
$statusFilter = in_array($_GET['stato'] ?? $_POST['stato'] ?? '', $allowedStatuses, true)
    ? ($_GET['stato'] ?? $_POST['stato'])
    : 'bozza';
$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activityId = (int)($_POST['activity_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($activityId > 0 && in_array($action, ['approva', 'blocca'], true)) {
        if ($action === 'approva') {
            $stmt = $pdo->prepare("UPDATE attivita_eventi SET Stato='pubblicata'
                                   WHERE ID_Attivita=? AND Data_Ora>NOW()");
            $stmt->execute([$activityId]);
            if ($stmt->rowCount() === 0) {
                $flash = 'L’evento non può essere approvato perché la data è già trascorsa.';
                $flashType = 'danger';
            } else {
                $flash = 'Evento approvato e pubblicato con successo.';
            }
        } else {
            $stmt = $pdo->prepare("UPDATE attivita_eventi SET Stato='cancellata' WHERE ID_Attivita=?");
            $stmt->execute([$activityId]);
            $flash = 'Evento bloccato con successo.';
        }
    } else {
        $flash = 'Operazione non valida.';
        $flashType = 'danger';
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 25;
$offset = ($page - 1) * $pageSize;
$stmt = $pdo->prepare("SELECT a.ID_Attivita AS id,a.Titolo AS titolo,a.Descrizione AS descrizione,
                              a.Tipo_Attivita AS tipo_attivita,a.Data_Ora AS data_ora,a.Stato AS stato,
                              a.Supporta_VR AS supporta_vr,i.Ragione_Sociale AS organizzatore,
                              i.Tipologia AS tipo_organizzatore
                       FROM attivita_eventi a
                       JOIN istituti_e_partner i ON i.ID_Ente=a.FK_Ente_Organizzatore
                       WHERE a.Stato=? ORDER BY a.created_at DESC LIMIT 26 OFFSET {$offset}");
$stmt->execute([$statusFilter]);
$events = $stmt->fetchAll();
$hasMore = count($events) > $pageSize;
if ($hasMore) {
    array_pop($events);
}
$statusLabels = ['bozza' => 'Da approvare', 'pubblicata' => 'Approvati', 'cancellata' => 'Bloccati'];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approvazione eventi - VR Open House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <main class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h1 class="h2 mb-0"><i class="bi bi-calendar2-check me-2"></i>Approvazione eventi</h1>
            <a href="dashboard_admin.php?lang=<?= htmlspecialchars($lang) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Torna alla dashboard
            </a>
        </div>

        <form method="GET" class="card shadow-sm mb-4">
            <div class="card-body row g-3 align-items-end">
                <input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
                <div class="col-md-9">
                    <label for="stato" class="form-label">Stato evento</label>
                    <select id="stato" name="stato" class="form-select">
                        <?php foreach ($statusLabels as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-2"></i>Visualizza</button>
                </div>
            </div>
        </form>

        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flashType) ?>" role="status"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between">
                <span>Eventi · <?= htmlspecialchars($statusLabels[$statusFilter]) ?></span>
                <span class="badge bg-light text-dark"><?= count($events) ?> risultati</span>
            </div>
            <div class="card-body p-0">
                <?php if (!$events): ?>
                    <p class="text-center text-muted p-5 mb-0"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Nessun evento in questo stato.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead><tr><th>Evento</th><th>Tipo</th><th>Organizzatore</th><th>Data</th><th>VR</th><th>Azioni</th></tr></thead>
                            <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($event['titolo']) ?></strong><br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($event['descrizione'], 0, 100, '…')) ?></small></td>
                                    <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $event['tipo_attivita']))) ?></td>
                                    <td><?= htmlspecialchars($event['organizzatore']) ?><br><small class="text-muted"><?= htmlspecialchars($event['tipo_organizzatore']) ?></small></td>
                                    <td><?= date('d/m/Y H:i', strtotime($event['data_ora'])) ?></td>
                                    <td><?= $event['supporta_vr'] ? 'Sì' : 'No' ?></td>
                                    <td>
                                        <form method="POST" class="d-flex flex-wrap gap-2">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
                                            <input type="hidden" name="stato" value="<?= htmlspecialchars($statusFilter) ?>">
                                            <input type="hidden" name="activity_id" value="<?= (int)$event['id'] ?>">
                                            <?php if ($event['stato'] !== 'pubblicata'): ?>
                                                <button type="submit" name="action" value="approva" class="btn btn-sm btn-success">Approva e pubblica</button>
                                            <?php endif; ?>
                                            <?php if ($event['stato'] !== 'cancellata'): ?>
                                                <button type="submit" name="action" value="blocca" class="btn btn-sm btn-outline-danger">Blocca</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-3"><?= paginationLinks($page, $hasMore) ?></div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
