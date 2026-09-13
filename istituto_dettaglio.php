<?php
require_once 'config.php';

$lang = $_GET['lang'] ?? 'it';
$istituto_id = (int) ($_GET['id'] ?? 0);

if ($istituto_id <= 0) {
    header('Location: istituti_elenco.php?lang=' . urlencode($lang));
    exit;
}

$selectExtra = '';
if (tableHasColumn($pdo, 'istituti_e_partner', 'Telefono')) {
    $selectExtra .= ", Telefono as telefono";
}
if (tableHasColumn($pdo, 'istituti_e_partner', 'descrizione')) {
    $selectExtra .= ", descrizione";
}

$stmt = $pdo->prepare("SELECT
                        ID_Ente as id,
                        Ragione_Sociale as nome,
                        Tipologia as tipo_scuola,
                        Cod_Mecc as codice_istituto,
                        Cod_REA as codice_rea,
                        Indirizzo as indirizzo,
                        Comune as comune,
                        Provincia as provincia,
                        Regione as regione,
                        Email as email
                        {$selectExtra}
                       FROM istituti_e_partner
                       WHERE ID_Ente = ?");
$stmt->execute([$istituto_id]);
$istituto = $stmt->fetch();

if (!$istituto) {
    header('Location: istituti_elenco.php?lang=' . urlencode($lang));
    exit;
}

$stmt = $pdo->prepare("SELECT a.ID_Attivita as id, a.Titolo as titolo, a.Descrizione as descrizione, a.Data_Ora as data_ora,
                       a.Supporta_VR as supporta_vr, a.Max_Posti as max_partecipanti, a.Stato as stato,
                       COALESCE(SUM(p.numero_partecipanti),0) as prenotazioni_count
                       FROM attivita_eventi a
                       LEFT JOIN prenotazioni p ON a.ID_Attivita = p.attivita_id AND p.stato = 'confermata'
                       WHERE a.FK_Ente_Organizzatore = ? AND a.Stato = 'pubblicata'
                       GROUP BY a.ID_Attivita
                       ORDER BY a.Data_Ora ASC");
$stmt->execute([$istituto_id]);
$attivita = $stmt->fetchAll();

$tipi_scuola_map = [
    'it' => [
        'infanzia' => 'Scuola dell\'Infanzia',
        'primaria' => 'Scuola Primaria',
        'secondaria_primo' => 'Scuola Secondaria di Primo Grado',
        'secondaria_secondo' => 'Scuola Secondaria di Secondo Grado',
        'universita' => 'Università',
    ],
    'en' => [
        'infanzia' => 'Kindergarten',
        'primaria' => 'Primary School',
        'secondaria_primo' => 'Lower Secondary School',
        'secondaria_secondo' => 'Upper Secondary School',
        'universita' => 'University',
    ],
];

$tipoLabel = $tipi_scuola_map[$lang][$istituto['tipo_scuola']] ?? $istituto['tipo_scuola'];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($istituto['nome']) ?> - Open House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <p class="mb-3">
            <a href="istituti_elenco.php?lang=<?= htmlspecialchars($lang) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Torna all'elenco istituti
            </a>
        </p>
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2><?= htmlspecialchars($istituto['nome']) ?></h2>
                        <?php if ($tipoLabel): ?>
                            <p class="text-muted">
                                <i class="bi bi-mortarboard"></i>
                                <?= htmlspecialchars($tipoLabel) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($istituto['codice_istituto'])): ?>
                            <p class="small text-muted mb-1">
                                <i class="bi bi-card-text"></i>
                                Cod. mecc.: <?= htmlspecialchars($istituto['codice_istituto']) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($istituto['codice_rea'])): ?>
                            <p class="small text-muted mb-1">
                                <i class="bi bi-card-text"></i>
                                Cod. REA: <?= htmlspecialchars($istituto['codice_rea']) ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($istituto['comune'] || $istituto['provincia'] || $istituto['regione']): ?>
                            <p>
                                <i class="bi bi-geo-alt"></i>
                                <?php
                                $geo = array_filter([
                                    $istituto['comune'] ?? '',
                                    $istituto['provincia'] ?? '',
                                    $istituto['regione'] ?? '',
                                ]);
                                echo htmlspecialchars(implode(', ', $geo));
                                ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($istituto['indirizzo'])): ?>
                            <p>
                                <i class="bi bi-house"></i>
                                <?= nl2br(htmlspecialchars($istituto['indirizzo'])) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($istituto['telefono'])): ?>
                            <p>
                                <i class="bi bi-telephone"></i>
                                <?= htmlspecialchars($istituto['telefono']) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($istituto['email'])): ?>
                            <p>
                                <i class="bi bi-envelope"></i>
                                <a href="mailto:<?= htmlspecialchars($istituto['email']) ?>">
                                    <?= htmlspecialchars($istituto['email']) ?>
                                </a>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($istituto['descrizione'])): ?>
                            <hr>
                            <h5>Descrizione</h5>
                            <p><?= nl2br(htmlspecialchars($istituto['descrizione'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Attività disponibili</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($attivita)): ?>
                            <p class="text-muted mb-0">Nessuna attività pubblicata al momento.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($attivita as $a): ?>
                                    <div class="list-group-item px-0">
                                        <h6 class="mb-1"><?= htmlspecialchars($a['titolo']) ?></h6>
                                        <p class="small text-muted mb-2">
                                            <i class="bi bi-calendar"></i>
                                            <?= date('d/m/Y H:i', strtotime($a['data_ora'])) ?>
                                        </p>
                                        <div class="mb-2">
                                            <?php if ($a['supporta_vr']): ?>
                                                <span class="badge bg-info"><i class="bi bi-vr"></i> VR</span>
                                            <?php endif; ?>
                                            <span class="badge bg-secondary">
                                                <?= (int) $a['prenotazioni_count'] ?>/<?= (int) $a['max_partecipanti'] ?>
                                            </span>
                                        </div>
                                        <a href="attivita_dettaglio.php?id=<?= (int) $a['id'] ?>&lang=<?= htmlspecialchars($lang) ?>"
                                           class="btn btn-sm btn-primary w-100">
                                            Dettagli
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
