<?php
require_once 'config.php';
requirePartner();

$lang = $_GET['lang'] ?? 'it';
$partner_user_id = $_SESSION['user_id'];

$stmt=$pdo->prepare("SELECT ID_Ente as id, Ragione_Sociale as ragione_sociale, Email as email,
    Comune as citta, Regione as regione, Tipologia as tipo_partner,
    'approvato' as stato_validazione, '' as nome, '' as cognome, '' as descrizione
    FROM istituti_e_partner WHERE ID_Ente=?");
$stmt->execute([$partner_user_id]);$partner=$stmt->fetch();
$stmt=$pdo->prepare("SELECT COUNT(*) FROM prenotazioni WHERE partner_vr_id=? AND stato='confermata'");
$stmt->execute([$partner_user_id]);$tot_prenotazioni=$stmt->fetchColumn();
$stmt=$pdo->prepare("SELECT COUNT(*) FROM attivita_eventi WHERE FK_Ente_Organizzatore=?");
$stmt->execute([$partner_user_id]);$tot_eventi=$stmt->fetchColumn();
$stmt=$pdo->prepare("SELECT ID_Attivita AS id,Titolo AS titolo,Tipo_Attivita AS tipo_attivita,Data_Ora AS data_ora,Stato AS stato
                     FROM attivita_eventi WHERE FK_Ente_Organizzatore=? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$partner_user_id]);$eventi_recenti=$stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Partner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="mb-0"><i class="bi bi-broadcast-pin"></i> Dashboard Partner</h2>
        <a href="attivita_nuova.php?lang=<?= htmlspecialchars($lang) ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Proponi un evento
        </a>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary"><div class="card-body">
                <h5 class="card-title">Eventi proposti</h5>
                <h3 class="mb-0"><?= (int)$tot_eventi ?></h3>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success"><div class="card-body">
                <h5 class="card-title">Prenotazioni ricevute</h5>
                <h3 class="mb-0"><?= (int)$tot_prenotazioni ?></h3>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-dark"><div class="card-body">
                <h5 class="card-title">Tipo partner</h5>
                <p class="mb-0"><?= htmlspecialchars($partner['tipo_partner'] ?? '-') ?></p>
            </div></div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">Eventi recenti</div>
        <div class="card-body p-0">
            <?php if (!$eventi_recenti): ?>
                <p class="p-3 mb-0 text-muted">Nessun evento proposto. Usa il pulsante “Proponi un evento”.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Titolo</th><th>Tipo</th><th>Data</th><th>Stato</th><th>Azioni</th></tr></thead>
                        <tbody>
                        <?php foreach ($eventi_recenti as $evento): ?>
                            <tr>
                                <td><?= htmlspecialchars($evento['titolo']) ?></td>
                                <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $evento['tipo_attivita']))) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($evento['data_ora'])) ?></td>
                                <td><span class="badge bg-<?= $evento['stato'] === 'pubblicata' ? 'success' : ($evento['stato'] === 'cancellata' ? 'danger' : 'warning text-dark') ?>"><?= $evento['stato'] === 'bozza' ? 'Da approvare' : ($evento['stato'] === 'cancellata' ? 'Bloccato' : 'Approvato') ?></span></td>
                                <td><a href="attivita_modifica.php?id=<?= (int)$evento['id'] ?>&lang=<?= htmlspecialchars($lang) ?>" class="btn btn-sm btn-outline-primary">Modifica</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">Profilo partner</div>
        <div class="card-body">
            <?php if (!$partner): ?>
                <p class="text-muted mb-0">Profilo partner non trovato. Completa la registrazione.</p>
            <?php else: ?>
                <p><strong>Ragione sociale:</strong> <?= htmlspecialchars($partner['ragione_sociale']) ?></p>
                <p><strong>Referente:</strong> <?= htmlspecialchars(trim(($partner['nome'] ?? '') . ' ' . ($partner['cognome'] ?? ''))) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($partner['email']) ?></p>
                <p><strong>Area:</strong> <?= htmlspecialchars(($partner['citta'] ?? '-') . ', ' . ($partner['regione'] ?? '-')) ?></p>
                <p class="mb-0"><strong>Descrizione:</strong> <?= htmlspecialchars($partner['descrizione'] ?? '-') ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="mt-3">
        <a href="attivita_gestione.php?lang=<?= htmlspecialchars($lang) ?>" class="btn btn-primary">Gestisci eventi</a>
        <a href="dashboard.php?lang=<?= htmlspecialchars($lang) ?>" class="btn btn-outline-primary">Aggiorna</a>
        <a href="logout.php?lang=<?= htmlspecialchars($lang) ?>" class="btn btn-outline-secondary">Logout</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
