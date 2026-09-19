<?php
require_once 'config.php';

$lang = $_GET['lang'] ?? 'it';

// Gestione errori database
$attivita_featured = [];
$total_istituti = 0;
$total_partner = 0;
$total_enti = 0;
$total_attivita = 0;
$total_utenti_registrati = 0;

try {
    // Tutte le attivita pubblicate (passate e future)
    $stmt = $pdo->prepare("SELECT a.ID_Attivita as id, a.Titolo as titolo, a.Descrizione as descrizione,
                           a.Data_Ora as data_ora, a.Supporta_VR as supporta_vr, a.Max_Posti as max_partecipanti,
                           i.Ragione_Sociale as istituto_nome, i.Tipologia as tipo_scuola,
                           COALESCE(SUM(p.numero_partecipanti),0) as prenotazioni_count
                           FROM attivita_eventi a
                           JOIN istituti_e_partner i ON a.FK_Ente_Organizzatore = i.ID_Ente
                           LEFT JOIN prenotazioni p ON a.ID_Attivita = p.attivita_id AND p.stato = 'confermata'
                           WHERE a.Stato = 'pubblicata' AND a.Data_Ora > NOW()
                           GROUP BY a.ID_Attivita
                           ORDER BY a.Data_Ora ASC");
    $stmt->execute();
    $attivita_featured = $stmt->fetchAll();

    // Usa gli stessi criteri delle pagine pubbliche: mostra solo enti approvati.
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM istituti_e_partner WHERE Stato_Validazione = 1 AND Cod_Mecc IS NOT NULL AND Cod_Mecc != ''");
    $total_istituti = (int)($stmt->fetch()['total'] ?? 0);

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM istituti_e_partner
                         WHERE Stato_Validazione = 1
                           AND (Tipologia IN ('ARENA_VR', 'ARENA_MOBILE', 'PARTNER_VR')
                                OR Tipologia LIKE '%AZIENDA%'
                                OR (Tipologia LIKE '%PARTNER%' AND Tipologia <> 'PARTNER_VR'))");
    $total_partner = (int)($stmt->fetch()['total'] ?? 0);

    $total_enti = $total_istituti + $total_partner;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM attivita_eventi WHERE Stato = 'pubblicata'");
    $total_attivita = $stmt->fetch()['total'] ?? 0;

    $userTable = null;
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('utenti', $tables, true)) {
        $userTable = 'utenti';
    } elseif (in_array('utenti_finali', $tables, true)) {
        $userTable = 'utenti_finali';
    }

    if ($userTable !== null) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM {$userTable}");
        $total_utenti_registrati = (int)($stmt->fetch()['total'] ?? 0);
    }
} catch(PDOException $e) {
    // Se il database non e ancora configurato, mostra valori di default
    // L'utente vedra la pagina ma senza dati
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Database error in index.php: " . $e->getMessage());
    }
}

$translations = [
    'it' => [
        'title' => 'VR Open House - Orientamento Immersivo',
        'hero_title' => 'VR Open House',
        'hero_subtitle' => 'Un mondo nuovo per avvicinare studenti, famiglie, scuola e lavoro',
        'scopri' => 'Scopri le Attivita',
        'registrati' => 'Registrati',
        'accedi' => 'Accedi',
        'istituti' => 'Istituti',
        'utenti_registrati' => 'Utenti registrati',
        'attivita' => 'Attivita',
        'partecipanti' => 'Partecipanti',
        'prenota' => 'Prenota',
        'dettagli' => 'Dettagli',
        'vr' => 'VR',
        'cosa_offriamo' => 'Cosa Offriamo',
        'per_istituti' => 'Per Istituti',
        'per_utenti' => 'Per Utenti',
        'per_partner' => 'Per Partner VR e FSL',
        'desc_per_istituti' => 'Dashboard per pubblicare laboratori, tour e moduli orientativi con contenuti 360/WebXR',
        'desc_per_utenti' => 'Ricerca attivita, prenotazione multimodale (casa o arena) e storico personale',
        'desc_per_partner' => 'Visibilita territoriale, supporto tecnico in Arena VR e mentorato nelle esperienze immersive',
        'inizia_ora' => 'Inizia Ora',
        'ecosistema' => 'Ecosistema VR Open House',
        'ecosistema_subtitle' => 'Un modello collaborativo tra scuole, famiglie, partner territoriali e mondo del lavoro',
        'attori' => 'Attori Principali',
        'casi_uso' => 'Esperienze',
        'rnf' => 'Requisiti qualitativi',
        'always_on' => 'Accesso online',
        'gdpr' => 'GDPR',
        'compatibilita' => 'Compatibile WebXR',
        'inclusivo' => 'Inclusivo e geolocalizzato',
        'responsive' => 'Responsive',
        'high_availability' => 'Esperienze immersive',
        'view_activities_hint' => 'Ogni pulsante apre il percorso o la risorsa indicata.',
        'open_day_virtuali' => 'Open House',
        'orientamento_uscita' => 'Orientamento in Uscita',
        'fsl_formazione' => 'Formazione Scuola-Lavoro FSL',
        'corsi_certificazioni' => 'Corsi e Certificazioni',
        'storico_fsl' => 'Storico personale e materiali FSL',
        'cta_join' => 'Unisciti alla piattaforma',
        'footer' => '&copy; 2026 Open House. Tutti i diritti riservati.',
        'utenti_finali' => 'Utenti Finali',
        'partner' => 'Partner'
    ],
    'en' => [
        'title' => 'VR Open House - Immersive Orientation',
        'hero_title' => 'VR Open House',
        'hero_subtitle' => 'School orientation and FSL in immersive WebXR environments, from home or VR arenas',
        'scopri' => 'Discover Activities',
        'registrati' => 'Register',
        'accedi' => 'Login',
        'istituti' => 'Institutions',
        'utenti_registrati' => 'Registered users',
        'attivita' => 'Activities',
        'partecipanti' => 'Participants',
        'prenota' => 'Book',
        'dettagli' => 'Details',
        'vr' => 'VR',
        'cosa_offriamo' => 'What We Offer',
        'per_istituti' => 'For Institutions',
        'per_utenti' => 'For Users',
        'per_partner' => 'For VR and FSL Partners',
        'desc_per_istituti' => 'Dashboard to publish labs, tours and orientation modules with 360/WebXR content',
        'desc_per_utenti' => 'Activity discovery, multimodal booking (home or arena) and personal history',
        'desc_per_partner' => 'Local visibility, technical support in VR arenas and mentoring in immersive sessions',
        'inizia_ora' => 'Get Started',
        'ecosistema' => 'VR Open House Ecosystem',
        'ecosistema_subtitle' => 'A collaborative model between schools, families, local partners and the job market',
        'attori' => 'Main Actors',
        'casi_uso' => 'Experiences',
        'rnf' => 'Quality requirements',
        'always_on' => 'Available 24/7',
        'gdpr' => 'GDPR',
        'compatibilita' => 'WebXR compatible',
        'inclusivo' => 'Inclusive and geolocated',
        'responsive' => 'Responsive',
        'high_availability' => 'Esperienze immersive',
        'view_activities_hint' => 'Each button opens the indicated pathway or resource.',
        'open_day_virtuali' => 'Open Houses',
        'orientamento_uscita' => 'Career Orientation',
        'fsl_formazione' => 'School-to-Work FSL Training',
        'corsi_certificazioni' => 'Courses and Certifications',
        'storico_fsl' => 'Personal history and FSL materials',
        'cta_join' => 'Join the platform',
        'footer' => '&copy; 2026 Open House. All rights reserved.',
        'utenti_finali' => 'End Users',
        'partner' => 'Partners'
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
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body class="home-page">
    <?php $active_page = 'home'; include 'header.php'; ?>
<!-- Hero Section -->
    <section class="bg-primary text-white py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-5 fw-bold mb-3 hero-title-fluo"><?= $t['hero_title'] ?></h1>
                    <p class="lead mb-3 hero-subtitle-fluo"><?= $t['hero_subtitle'] ?></p>
                    <div class="d-flex gap-3">
                        <a href="register.php?lang=<?= $lang ?>" class="btn btn-light btn-lg"><?= $t['registrati'] ?></a>
                        <a href="attivita_elenco.php?lang=<?= $lang ?>" class="btn btn-outline-light btn-lg"><?= $t['scopri'] ?></a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="bi bi-vr" style="font-size: 10rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3">
                    <h2 class="display-4 text-primary"><?= $total_istituti ?></h2>
                    <p class="lead"><?= $t['istituti'] ?></p>
                </div>
                <div class="col-md-3">
                    <h2 class="display-4 text-warning"><?= $total_partner ?></h2>
                    <p class="lead"><?= $t['partner'] ?></p>
                </div>
                <div class="col-md-3">
                    <h2 class="display-4 text-success"><?= $total_attivita ?></h2>
                    <p class="lead"><?= $t['attivita'] ?></p>
                </div>
                <div class="col-md-3">
                    <h2 class="display-4 text-info"><?= $total_utenti_registrati ?></h2>
                    <p class="lead"><?= $t['utenti_registrati'] ?></p>
                </div>
                <div class="col-md-3">
                    <h2 class="display-4 text-warning"><i class="bi bi-clock-history"></i></h2>
                    <p class="lead"><?= $t['always_on'] ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5"><?= $t['cosa_offriamo'] ?></h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center p-5">
                            <i class="bi bi-building display-1 text-primary mb-3"></i>
                            <h3><?= $t['per_istituti'] ?></h3>
                            <p class="text-muted"><?= $t['desc_per_istituti'] ?></p>
                            <a href="register.php?user_type=istituto&lang=<?= $lang ?>" class="btn btn-primary"><?= $t['inizia_ora'] ?></a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center p-5">
                            <i class="bi bi-people display-1 text-success mb-3"></i>
                            <h3><?= $t['per_utenti'] ?></h3>
                            <p class="text-muted"><?= $t['desc_per_utenti'] ?></p>
                            <a href="register.php?user_type=utente&lang=<?= $lang ?>" class="btn btn-success"><?= $t['inizia_ora'] ?></a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center p-5">
                            <i class="bi bi-broadcast-pin display-1 text-info mb-3"></i>
                            <h3><?= $t['per_partner'] ?></h3>
                            <p class="text-muted"><?= $t['desc_per_partner'] ?></p>
                            <a href="register.php?lang=<?= $lang ?>" class="btn btn-info text-white"><?= $t['inizia_ora'] ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Ecosystem Section -->
    <section class="py-5 bg-light" id="ecosistema">
        <div class="container">
            <h2 class="text-center mb-2"><?= $t['ecosistema'] ?></h2>
            <p class="text-center text-muted mb-5"><?= $t['ecosistema_subtitle'] ?></p>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-diagram-3"></i> <?= $t['attori'] ?></h5>
                            <div class="d-grid gap-2">
                                    <a href="istituti_elenco.php?solo=istituti&lang=<?= $lang ?>" class="btn btn-outline-primary text-start">1. Istituti (scuole, universita, accademie, ITS)</a>
                                    <a href="partner_istituti.php?view=partner_vr&lang=<?= $lang ?>" class="btn btn-outline-primary text-start">2. Partner VR (Arena fisse e mobili)</a>
                                    <a href="partner_istituti.php?view=partner_fsl&lang=<?= $lang ?>" class="btn btn-outline-primary text-start">3. Partner FSL (aziende e istituzioni)</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-list-check"></i> <?= $t['casi_uso'] ?></h5>
                            <div class="d-grid gap-2 mb-3">
                                <a href="attivita_elenco.php?lang=<?= $lang ?>&tipo=open_day" class="btn btn-outline-success text-start">1. <?= $t['open_day_virtuali'] ?></a>
                                <a href="partner_istituti.php?lang=<?= $lang ?>&view=partner_fsl" class="btn btn-outline-success text-start">2. <?= $t['orientamento_uscita'] ?></a>
                                <a href="attivita_elenco.php?lang=<?= $lang ?>&fsl=1" class="btn btn-outline-success text-start">3. <?= $t['fsl_formazione'] ?></a>
                                <a href="corsi_certificazioni.php?lang=<?= $lang ?>" class="btn btn-outline-success text-start">4. <?= $t['corsi_certificazioni'] ?></a>
                                <a href="dashboard.php?lang=<?= $lang ?>#storico-fsl" class="btn btn-outline-success text-start">5. <?= $t['storico_fsl'] ?></a>
                            </div>
                            <small class="text-muted"><?= $t['view_activities_hint'] ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-shield-check"></i> <?= $t['rnf'] ?></h5>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Piattaforma attiva in qualsiasi momento, con accesso continuo ai contenuti."><?= $t['always_on'] ?></button>
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="tooltip" data-bs-placement="top" title="Gestione dei dati personali conforme alla normativa europea sulla privacy."><?= $t['gdpr'] ?></button>
                                <button type="button" class="btn btn-sm btn-info text-dark" data-bs-toggle="tooltip" data-bs-placement="top" title="Esperienze immersive fruibili da browser e visori compatibili con WebXR."><?= $t['compatibilita'] ?></button>
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="Percorsi accessibili, inclusivi e geolocalizzati per supportare orientamento territoriale."><?= $t['inclusivo'] ?></button>
                                <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="tooltip" data-bs-placement="top" title="Interfaccia adattabile a smartphone, tablet, desktop e postazioni Arena."><?= $t['responsive'] ?></button>
                                <button type="button" class="btn btn-sm btn-warning text-dark" data-bs-toggle="tooltip" data-bs-placement="top" title="Architettura ridondata e monitorata per garantire continuita del servizio."><?= $t['high_availability'] ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5">
                <a href="register.php?lang=<?= $lang ?>" class="btn btn-lg btn-primary"><?= $t['cta_join'] ?></a>
            </div>
        </div>
    </section>

    <!-- Featured Activities -->
    <?php if (!empty($attivita_featured)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5"><?= $t['attivita'] ?></h2>
            <div class="row">
                <?php foreach ($attivita_featured as $attivita): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($attivita['titolo']) ?></h5>
                                <p class="text-muted small">
                                    <i class="bi bi-building"></i> <?= htmlspecialchars($attivita['istituto_nome']) ?>
                                </p>
                                <p class="card-text"><?= htmlspecialchars(substr($attivita['descrizione'], 0, 100)) ?>...</p>
                                <p class="small text-muted">
                                    <i class="bi bi-calendar"></i> <?= date('d/m/Y H:i', strtotime($attivita['data_ora'])) ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ($attivita['supporta_vr']): ?>
                                            <span class="badge bg-info"><i class="bi bi-vr"></i> <?= $t['vr'] ?></span>
                                        <?php endif; ?>
                                        <span class="badge bg-secondary"><?= $attivita['prenotazioni_count'] ?>/<?= $attivita['max_partecipanti'] ?></span>
                                    </div>
                                    <a href="attivita_dettaglio.php?id=<?= $attivita['id'] ?>&lang=<?= $lang ?>" class="btn btn-sm btn-primary">
                                        <?= $t['dettagli'] ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="attivita_elenco.php?lang=<?= $lang ?>" class="btn btn-primary btn-lg"><?= $t['scopri'] ?></a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-0"><?= $t['footer'] ?></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>
