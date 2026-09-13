<?php
require_once 'config.php';
requireAdmin();

$lang = $_GET['lang'] ?? 'it';

$stmt = $pdo->query("SELECT COUNT(*) AS totale FROM istituti_e_partner WHERE Stato_Validazione = 0 AND Cod_Mecc IS NOT NULL AND Cod_Mecc <> ''");
$istituti_in_attesa = $stmt->fetch()['totale'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) AS totale FROM istituti_e_partner WHERE Stato_Validazione=0 AND Cod_REA IS NOT NULL AND Cod_REA <> ''");
$partner_in_attesa = $stmt->fetch()['totale'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) AS totale FROM prenotazioni");
$tot_prenotazioni = $stmt->fetch()['totale'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) AS totale FROM attivita_eventi WHERE Stato = 'bozza'");
$eventi_in_attesa = $stmt->fetch()['totale'] ?? 0;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="mb-0"><i class="bi bi-shield-check"></i> Dashboard Amministratore</h2>
        <a href="index.php?lang=<?= htmlspecialchars($lang) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Torna alla home
        </a>
    </div>
    <div class="row g-3">
        <div class="col-md-3">
            <a href="admin_validazione_enti.php?tipo=istituti&stato=0&lang=<?= htmlspecialchars($lang) ?>" class="card text-white bg-warning text-decoration-none">
                <div class="card-body">
                    <h5>Istituti da validare</h5>
                    <h2 class="mb-0"><?= (int)$istituti_in_attesa ?></h2>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="admin_validazione_enti.php?tipo=partner&stato=0&lang=<?= htmlspecialchars($lang) ?>" class="card text-white bg-info text-decoration-none">
                <div class="card-body">
                    <h5>Partner da validare</h5>
                    <h2 class="mb-0"><?= (int)$partner_in_attesa ?></h2>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="admin_attivita.php?stato=bozza&lang=<?= htmlspecialchars($lang) ?>" class="card text-white bg-dark text-decoration-none">
                <div class="card-body">
                    <h5>Eventi da approvare</h5>
                    <h2 class="mb-0"><?= (int)$eventi_in_attesa ?></h2>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5>Prenotazioni totali</h5>
                    <h2 class="mb-0"><?= (int)$tot_prenotazioni ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex flex-wrap gap-2">
        <a href="admin_validazione_enti.php?tipo=utenti&stato=1&lang=<?= htmlspecialchars($lang) ?>" class="btn btn-primary">Gestisci utenti</a>
        <a href="admin_validazione_enti.php?tipo=istituti&stato=0&lang=<?= htmlspecialchars($lang) ?>" class="btn btn-primary">Gestisci istituti</a>
        <a href="admin_validazione_enti.php?tipo=partner&stato=0&lang=<?= htmlspecialchars($lang) ?>" class="btn btn-primary">Gestisci partner</a>
        <a href="admin_attivita.php?stato=bozza&lang=<?= htmlspecialchars($lang) ?>" class="btn btn-dark">Approva eventi</a>
        <a href="admin_password.php" class="btn btn-outline-primary">Cambia password</a>
        <a href="logout.php?lang=<?= htmlspecialchars($lang) ?>" class="btn btn-outline-secondary">Logout</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
