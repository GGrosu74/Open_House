<?php
require_once 'config.php';
requireAdmin();

$lang = $_GET['lang'] ?? $_POST['lang'] ?? 'it';
$lang = in_array($lang, ['it', 'en'], true) ? $lang : 'it';
$allowedTypes = ['utenti', 'istituti', 'partner'];
$tipoFilter = in_array($_GET['tipo'] ?? $_POST['tipo'] ?? '', $allowedTypes, true)
    ? ($_GET['tipo'] ?? $_POST['tipo'])
    : 'partner';
$statusValue = (string)($_GET['stato'] ?? $_POST['stato'] ?? '0');
$statusFilter = in_array($statusValue, ['0', '1', '2'], true) ? (int)$statusValue : 0;
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 25;
$offset = ($page - 1) * $pageSize;
$flash = '';
$flashType = 'success';
$userTable = getUserTable($pdo, false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recordId = (int)($_POST['record_id'] ?? $_POST['ente_id'] ?? 0);
    $recordType = in_array($_POST['record_type'] ?? '', $allowedTypes, true)
        ? $_POST['record_type']
        : $tipoFilter;
    $action = $_POST['action'] ?? '';

    if ($recordId > 0 && in_array($action, ['approva', 'blocca'], true)) {
        $newStatus = $action === 'approva' ? 1 : 2;
        if ($recordType === 'utenti' && $userTable) {
            $stmt = $pdo->prepare("UPDATE {$userTable} SET Stato_Validazione = ? WHERE id = ?");
        } else {
            $stmt = $pdo->prepare('UPDATE istituti_e_partner SET Stato_Validazione = ? WHERE ID_Ente = ?');
        }
        $stmt->execute([$newStatus, $recordId]);
        $flash = $action === 'approva'
            ? 'Account approvato con successo.'
            : 'Account bloccato con successo.';
    } else {
        $flash = 'Operazione non valida.';
        $flashType = 'danger';
    }
}

$records = [];
if ($tipoFilter === 'utenti' && $userTable) {
    $stmt = $pdo->prepare("SELECT id AS record_id,
                                  CONCAT(nome, ' ', cognome) AS nome,
                                  tipo_utente AS tipologia,
                                  NULL AS Cod_Mecc, NULL AS Cod_REA,
                                  email AS Email, telefono AS Telefono,
                                  Stato_Validazione
                           FROM {$userTable}
                           WHERE Stato_Validazione = ?
                           ORDER BY nome, cognome LIMIT 26 OFFSET {$offset}");
    $stmt->execute([$statusFilter]);
} else {
    $categoryCondition = $tipoFilter === 'istituti'
        ? "Cod_Mecc IS NOT NULL AND Cod_Mecc <> ''"
        : "Cod_REA IS NOT NULL AND Cod_REA <> ''";
    $stmt = $pdo->prepare("SELECT ID_Ente AS record_id, Ragione_Sociale AS nome,
                                  Tipologia AS tipologia, Cod_Mecc, Cod_REA,
                                  Email, Telefono, Stato_Validazione
                           FROM istituti_e_partner
                           WHERE Stato_Validazione = ? AND {$categoryCondition}
                           ORDER BY Ragione_Sociale LIMIT 26 OFFSET {$offset}");
    $stmt->execute([$statusFilter]);
}
$records = $stmt->fetchAll();
$hasMore = count($records) > $pageSize;
if ($hasMore) {
    array_pop($records);
}

$typeLabels = ['utenti' => 'Utenti', 'istituti' => 'Istituti', 'partner' => 'Partner'];
$statusLabels = [0 => 'Da approvare', 1 => 'Approvati', 2 => 'Bloccati'];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione account - VR Open House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <main class="container py-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <h1 class="h2 mb-0"><i class="bi bi-people-fill me-2"></i>Gestione account</h1>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                    <i class="bi bi-arrow-left me-2"></i>Indietro
                </button>
                <a href="dashboard_admin.php?lang=<?= htmlspecialchars($lang) ?>" class="btn btn-primary">
                    <i class="bi bi-speedometer2 me-2"></i>Torna alla dashboard
                </a>
            </div>
        </div>

        <form method="GET" class="card shadow-sm mb-4">
            <div class="card-body">
                <input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="tipo" class="form-label">Tipo account</label>
                        <select id="tipo" name="tipo" class="form-select">
                            <?php foreach ($typeLabels as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $tipoFilter === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="stato" class="form-label">Stato</label>
                        <select id="stato" name="stato" class="form-select">
                            <?php foreach ($statusLabels as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-2"></i>Visualizza</button>
                    </div>
                </div>
            </div>
        </form>

        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flashType) ?>" role="status"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?= htmlspecialchars($typeLabels[$tipoFilter]) ?> · <?= htmlspecialchars($statusLabels[$statusFilter]) ?></span>
                <span class="badge bg-light text-dark"><?= count($records) ?> risultati</span>
            </div>
            <div class="card-body p-0">
                <?php if (!$records): ?>
                    <div class="text-center text-muted p-5">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        Nessun account corrisponde ai filtri selezionati.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Tipologia</th>
                                    <?php if ($tipoFilter !== 'utenti'): ?>
                                        <th><?= $tipoFilter === 'partner' ? 'Codice REA' : 'Codice meccanografico' ?></th>
                                    <?php endif; ?>
                                    <th>Email</th>
                                    <th>Telefono</th>
                                    <th>Stato</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records as $record): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($record['nome']) ?></td>
                                        <td><?= htmlspecialchars($record['tipologia'] ?? '-') ?></td>
                                        <?php if ($tipoFilter !== 'utenti'): ?>
                                            <td><?= htmlspecialchars($tipoFilter === 'partner' ? ($record['Cod_REA'] ?? '-') : ($record['Cod_Mecc'] ?? '-')) ?></td>
                                        <?php endif; ?>
                                        <td><?= htmlspecialchars($record['Email'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($record['Telefono'] ?? '-') ?></td>
                                        <td>
                                            <?php if ((int)$record['Stato_Validazione'] === 0): ?>
                                                <span class="badge bg-warning text-dark">Da approvare</span>
                                            <?php elseif ((int)$record['Stato_Validazione'] === 1): ?>
                                                <span class="badge bg-success">Approvato</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Bloccato</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-flex flex-wrap gap-2">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
                                                <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipoFilter) ?>">
                                                <input type="hidden" name="stato" value="<?= $statusFilter ?>">
                                                <input type="hidden" name="record_type" value="<?= htmlspecialchars($tipoFilter) ?>">
                                                <input type="hidden" name="record_id" value="<?= (int)$record['record_id'] ?>">
                                                <?php if ((int)$record['Stato_Validazione'] !== 1): ?>
                                                    <button type="submit" name="action" value="approva" class="btn btn-sm btn-success">Approva</button>
                                                <?php endif; ?>
                                                <?php if ((int)$record['Stato_Validazione'] !== 2): ?>
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

        <div class="mt-3">
            <?= paginationLinks($page, $hasMore) ?>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
