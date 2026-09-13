<?php
require_once 'config.php';

$lang = $_GET['lang'] ?? 'it';
$search = $_GET['search'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$solo_fsl = ($_GET['fsl'] ?? '') === '1';

$query = "SELECT a.ID_Attivita as id, a.Titolo as titolo, a.Descrizione as descrizione, a.Data_Ora as data_ora,
          a.Supporta_VR as supporta_vr, a.Max_Posti as max_partecipanti, a.Tipo_Attivita as tipo_attivita,
          a.Link_WebXR as link_webxr, i.Ragione_Sociale as istituto_nome, i.Tipologia as tipo_scuola,
          COALESCE(SUM(p.numero_partecipanti),0) as prenotazioni_count
          FROM attivita_eventi a
          LEFT JOIN istituti_e_partner i ON a.FK_Ente_Organizzatore = i.ID_Ente
          LEFT JOIN prenotazioni p ON a.ID_Attivita = p.attivita_id AND p.stato = 'confermata'
          WHERE a.Stato = 'pubblicata'";

$params = [];

if (!empty($search)) {
    $query .= " AND (a.Titolo LIKE ? OR a.Descrizione LIKE ? OR i.Ragione_Sociale LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if (!empty($tipo)) {
    $query .= " AND a.Tipo_Attivita = ?";
    $params[] = $tipo;
}

if ($solo_fsl) {
    $query .= " AND a.Flag_FSL = 1";
}

$query .= " GROUP BY a.ID_Attivita ORDER BY a.Data_Ora ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$attivita = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elenco Attività</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <h2 class="mb-4"><?= $solo_fsl ? 'Attività Formazione Scuola-Lavoro (FSL)' : ($tipo === 'open_day' ? 'Open House disponibili' : 'Attività Disponibili') ?></h2>

        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="lang" value="<?= $lang ?>">
                    <?php if ($solo_fsl): ?><input type="hidden" name="fsl" value="1"><?php endif; ?>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="search" placeholder="Cerca..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="tipo">
                            <option value="">Tutti i tipi</option>
                            <option value="presentazione" <?= $tipo === 'presentazione' ? 'selected' : '' ?>>Presentazione</option>
                            <option value="laboratorio" <?= $tipo === 'laboratorio' ? 'selected' : '' ?>>Laboratorio</option>
                            <option value="tour_virtuale" <?= $tipo === 'tour_virtuale' ? 'selected' : '' ?>>Tour Virtuale</option>
                            <option value="open_day" <?= $tipo === 'open_day' ? 'selected' : '' ?>>Open House</option>
                            <option value="workshop" <?= $tipo === 'workshop' ? 'selected' : '' ?>>Workshop</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Cerca</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <?php if (empty($attivita)): ?>
                <div class="col-12">
                    <div class="alert alert-info">Nessuna attività trovata</div>
                </div>
            <?php else: ?>
                <?php foreach ($attivita as $a): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($a['titolo']) ?></h5>
                                    <p class="text-muted small">
                                    <i class="bi bi-building"></i> <?= htmlspecialchars($a['istituto_nome'] ?? 'Ente non assegnato') ?>
                                </p>
                                <p class="card-text"><?= htmlspecialchars(substr($a['descrizione'], 0, 150)) ?>...</p>
                                <p class="small">
                                    <i class="bi bi-calendar"></i> <?= date('d/m/Y H:i', strtotime($a['data_ora'])) ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ($a['supporta_vr']): ?>
                                            <span class="badge bg-info"><i class="bi bi-vr"></i> VR</span>
                                        <?php endif; ?>
                                        <span class="badge bg-secondary"><?= $a['prenotazioni_count'] ?>/<?= $a['max_partecipanti'] ?></span>
                                    </div>
                                    <a href="attivita_dettaglio.php?id=<?= $a['id'] ?>&lang=<?= $lang ?>" class="btn btn-sm btn-primary">
                                        Dettagli
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
