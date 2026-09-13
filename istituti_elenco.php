<?php
require_once 'config.php';

$lang = $_GET['lang'] ?? 'it';
$solo = $_GET['solo'] ?? '';

// Mapping tipologie tra codici filtro e valori database
$tipologie_map = [
    'infanzia' => 'SCUOLA INFANZIA',
    'primaria' => 'SCUOLA PRIMARIA',
    'secondaria_primo' => 'SCUOLA PRIMO GRADO',
    'secondaria_secondo' => 'SCUOLA SECONDARIA DI SECONDO GRADO',
    'istituto_comprensivo' => 'ISTITUTO COMPRENSIVO',
    'liceo_scientifico' => 'LICEO SCIENTIFICO',
    'liceo_classico' => 'LICEO CLASSICO',
    'liceo_artistico' => 'LICEO ARTISTICO',
    'istituto_tecnico' => 'ISTITUTO TECNICO',
    'istituto_professionale' => 'ISTITUTO PROFESSIONALE',
    'universita' => 'UNIVERSITÀ',
    'accademia' => 'ACCADEMIA',
    'azienda' => 'AZIENDA',
    'arena_vr' => 'ARENA_VR',
    'arena_mobile' => 'ARENA_MOBILE'
];

// Parametri di ricerca
$regione = $_GET['regione'] ?? '';
$provincia = $_GET['provincia'] ?? '';
$tipologia_ente = $_GET['tipologia_ente'] ?? ($_GET['tipo_scuola'] ?? '');
$search = $_GET['search'] ?? '';

// Converti il codice tipologia nel valore del database
$tipologia_db = !empty($tipologia_ente) && isset($tipologie_map[$tipologia_ente]) ? $tipologie_map[$tipologia_ente] : '';

// Costruisci query
$query = "SELECT i.*, i.ID_Ente as id, i.Ragione_Sociale as nome, i.Tipologia as tipo_scuola, i.Cod_Mecc as codice_istituto,
                 i.Indirizzo as indirizzo, i.Provincia as provincia, i.Regione as regione, i.Comune as comune,
                 NULL as descrizione,
                 (SELECT COUNT(*) FROM attivita_eventi a
                  WHERE a.FK_Ente_Organizzatore = i.ID_Ente AND a.Stato = 'pubblicata') as totale_attivita,
                 (SELECT COUNT(*) FROM prenotazioni p
                  INNER JOIN attivita_eventi a ON p.attivita_id = a.ID_Attivita
                  WHERE a.FK_Ente_Organizzatore = i.ID_Ente AND p.stato = 'confermata') as totale_prenotazioni
          FROM istituti_e_partner i
          WHERE i.Stato_Validazione=1";

$params = [];

if (!empty($regione)) {
    $query .= " AND i.Regione = ?";
    $params[] = $regione;
}

if (!empty($provincia)) {
    $query .= " AND i.Provincia = ?";
    $params[] = $provincia;
}

if (!empty($tipologia_db)) {
    $query .= " AND i.Tipologia = ?";
    $params[] = $tipologia_db;
}

if ($solo === 'istituti') {
    $query .= " AND i.Cod_Mecc IS NOT NULL AND i.Cod_Mecc != ''";
}

if (!empty($search)) {
    $query .= " AND (i.Ragione_Sociale LIKE ? OR i.Indirizzo LIKE ? OR i.Comune LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$query .= " ORDER BY i.Ragione_Sociale ASC";

$page=max(1,(int)($_GET['page']??1)); $pageSize=25; $offset=($page-1)*$pageSize;
$query.=' LIMIT '.($pageSize+1).' OFFSET '.$offset;
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$istituti = $stmt->fetchAll();
$hasMore=count($istituti)>$pageSize; if($hasMore) array_pop($istituti);

// Ottieni regioni e province uniche per i filtri
$stmt = $pdo->query("SELECT DISTINCT Regione FROM istituti_e_partner WHERE Regione IS NOT NULL AND Regione != '' ORDER BY Regione");
$regioni = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("SELECT DISTINCT Provincia FROM istituti_e_partner WHERE Provincia IS NOT NULL AND Provincia != '' ORDER BY Provincia");
$province = $stmt->fetchAll(PDO::FETCH_COLUMN);

$translations = [
    'it' => [
        'title' => 'Elenco Enti e Partner',
        'cerca_istituti' => 'Cerca Enti e Partner',
        'ricerca' => 'Ricerca',
        'regione' => 'Regione',
        'provincia' => 'Provincia',
        'tipo_scuola' => 'Tipologia Ente',
        'tutti' => 'Tutti',
        'cerca' => 'Cerca',
        'reset' => 'Reset',
        'nome' => 'Nome',
        'tipo' => 'Tipo',
        'indirizzo' => 'Indirizzo',
        'attivita' => 'Attività WebXR',
        'prenotazioni' => 'Prenotazioni',
        'dettagli' => 'Scheda Ente',
        'nessun_istituto' => 'Nessun ente trovato',
        'visualizza_attivita' => 'Visualizza Attività',
        'placeholder_ricerca' => 'Cerca per nome ente, descrizione o indirizzo...'
    ],
    'en' => [
        'title' => 'Organizations and Partners',
        'cerca_istituti' => 'Search Organizations and Partners',
        'ricerca' => 'Search',
        'regione' => 'Region',
        'provincia' => 'Province',
        'tipo_scuola' => 'Organization Type',
        'tutti' => 'All',
        'cerca' => 'Search',
        'reset' => 'Reset',
        'nome' => 'Name',
        'tipo' => 'Type',
        'indirizzo' => 'Address',
        'attivita' => 'WebXR Activities',
        'prenotazioni' => 'Bookings',
        'dettagli' => 'Organization Details',
        'nessun_istituto' => 'No organizations found',
        'visualizza_attivita' => 'View Activities',
        'placeholder_ricerca' => 'Search by organization name, description or address...'
    ]
];

$t = $translations[$lang];

$page_title = $solo === 'istituti'
    ? ($lang === 'it' ? 'Istituti' : 'Institutions')
    : $t['title'];

// Mappa tipologia ente (retrocompatibile con il campo storico tipo_scuola)
$tipologie_ente_map = [
    'it' => [
        'infanzia' => 'Scuola dell\'Infanzia',
        'primaria' => 'Scuola Primaria',
        'secondaria_primo' => 'Scuola Secondaria di Primo Grado',
        'secondaria_secondo' => 'Scuola Secondaria di Secondo Grado',
        'universita' => 'Università',
        'accademia' => 'Accademia / Alta Formazione',
        'azienda' => 'Azienda',
        'ospedale' => 'Ospedale',
        'tribunale' => 'Tribunale',
        'istituzione' => 'Istituzione',
        'club_sportivo' => 'Club Sportivo',
        'arena_vr' => 'Arena VR (Partner Tecnico)',
        'arena_mobile' => 'Arena Mobile (Partner Tecnico)'
    ],
    'en' => [
        'infanzia' => 'Kindergarten',
        'primaria' => 'Primary School',
        'secondaria_primo' => 'Lower Secondary School',
        'secondaria_secondo' => 'Upper Secondary School',
        'universita' => 'University',
        'accademia' => 'Academy / Higher Education',
        'azienda' => 'Company',
        'ospedale' => 'Hospital',
        'tribunale' => 'Court',
        'istituzione' => 'Institution',
        'club_sportivo' => 'Sports Club',
        'arena_vr' => 'VR Arena (Technical Partner)',
        'arena_mobile' => 'Mobile Arena (Technical Partner)'
    ]
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <h2 class="mb-4"><?= $t['cerca_istituti'] ?></h2>

        <!-- Form di ricerca -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="lang" value="<?= $lang ?>">
                    <?php if ($solo === 'istituti'): ?>
                        <input type="hidden" name="solo" value="istituti">
                    <?php endif; ?>

                    <div class="col-md-12">
                        <label for="search" class="form-label"><?= $t['ricerca'] ?></label>
                        <input type="text" class="form-control" id="search" name="search"
                               placeholder="<?= $t['placeholder_ricerca'] ?>"
                               value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="regione" class="form-label"><?= $t['regione'] ?></label>
                        <select class="form-select" id="regione" name="regione">
                            <option value=""><?= $t['tutti'] ?></option>
                            <?php foreach ($regioni as $r): ?>
                                <option value="<?= htmlspecialchars($r) ?>" <?= $regione === $r ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="provincia" class="form-label"><?= $t['provincia'] ?></label>
                        <select class="form-select" id="provincia" name="provincia">
                            <option value=""><?= $t['tutti'] ?></option>
                            <?php foreach ($province as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= $provincia === $p ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="tipologia_ente" class="form-label"><?= $t['tipo_scuola'] ?></label>
                        <select class="form-select" id="tipologia_ente" name="tipologia_ente">
                            <option value=""><?= $t['tutti'] ?></option>
                            <?php foreach ($tipologie_ente_map[$lang] as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $tipologia_ente === $key ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> <?= $t['cerca'] ?>
                        </button>
                        <a href="istituti_elenco.php?lang=<?= $lang ?><?= $solo === 'istituti' ? '&solo=istituti' : '' ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> <?= $t['reset'] ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Risultati -->
        <div class="row">
            <?php if (empty($istituti)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> <?= $t['nessun_istituto'] ?>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($istituti as $istituto): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($istituto['nome']) ?></h5>
                                <p class="text-muted small mb-2">
                                    <i class="bi bi-buildings"></i>
                                    <?= $tipologie_ente_map[$lang][$istituto['tipo_scuola']] ?? $istituto['tipo_scuola'] ?>
                                </p>

                                <?php if ($istituto['provincia']): ?>
                                    <p class="small mb-1">
                                        <i class="bi bi-geo-alt"></i>
                                        <?= htmlspecialchars($istituto['provincia']) ?>
                                        <?php if ($istituto['regione']): ?>
                                            - <?= htmlspecialchars($istituto['regione']) ?>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>

                                <?php if ($istituto['indirizzo']): ?>
                                    <p class="small text-muted mb-2">
                                        <i class="bi bi-house"></i> <?= htmlspecialchars(substr($istituto['indirizzo'], 0, 50)) ?>...
                                    </p>
                                <?php endif; ?>

                                <?php if ($istituto['descrizione']): ?>
                                    <p class="card-text small">
                                        <?= htmlspecialchars(substr($istituto['descrizione'], 0, 100)) ?>...
                                    </p>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        <span class="badge bg-info">
                                            <i class="bi bi-calendar-event"></i> <?= $istituto['totale_attivita'] ?> <?= $t['attivita'] ?>
                                        </span>
                                        <span class="badge bg-success ms-1">
                                            <i class="bi bi-people"></i> <?= $istituto['totale_prenotazioni'] ?> <?= $t['prenotazioni'] ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <a href="istituto_dettaglio.php?id=<?= $istituto['id'] ?>&lang=<?= $lang ?>"
                                       class="btn btn-sm btn-primary w-100">
                                        <i class="bi bi-eye"></i> <?= $t['dettagli'] ?>
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
<?= paginationLinks($page??1,$hasMore??false) ?>
</body>
</html>
