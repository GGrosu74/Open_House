<?php
require_once 'config.php';

$lang = $_GET['lang'] ?? 'it';
$attivita_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT a.ID_Attivita as id, a.FK_Ente_Organizzatore as organizzatore_id, a.Titolo as titolo, a.Descrizione as descrizione, a.Data_Ora as data_ora,
                       a.Durata_Minuti as durata_minuti, a.Supporta_VR as supporta_vr, a.Max_Posti as max_partecipanti, a.Tipo_Attivita as tipo_attivita,
                       a.Link_WebXR as link_webxr, a.Materiali_URL as materiali, a.Stato as stato,
                       i.Ragione_Sociale as istituto_nome, i.Email as istituto_email,
                       COALESCE(SUM(p.numero_partecipanti),0) as prenotazioni_count
                       FROM attivita_eventi a
                       JOIN istituti_e_partner i ON a.FK_Ente_Organizzatore = i.ID_Ente
                       LEFT JOIN prenotazioni p ON a.ID_Attivita = p.attivita_id AND p.stato = 'confermata'
                       WHERE a.ID_Attivita = ?
                       GROUP BY a.ID_Attivita");
$stmt->execute([$attivita_id]);
$attivita = $stmt->fetch();

if (!$attivita) {
    header('Location: index.php');
    exit;
}

if ($attivita['stato'] !== 'pubblicata') {
    $canPreview = isLoggedIn() && (
        $_SESSION['user_type'] === 'admin'
        || (in_array($_SESSION['user_type'], ['istituto', 'partner'], true)
            && (int)$_SESSION['user_id'] === (int)$attivita['organizzatore_id'])
    );
    if (!$canPreview) {
        http_response_code(404);
        exit('Evento non disponibile.');
    }
}

$flash_success = $_SESSION['success'] ?? '';
$flash_error = $_SESSION['error'] ?? '';
$booking_popup = $_SESSION['booking_popup'] ?? [];
unset($_SESSION['success'], $_SESSION['error'], $_SESSION['booking_popup']);

// Verifica se l'utente ha già prenotato
$ha_prenotato = false;
if (isLoggedIn()) {
    if (in_array($_SESSION['user_type'], ['istituto', 'partner'], true)) {
        $stmt = $pdo->prepare("SELECT id FROM prenotazioni WHERE istituto_prenotante_id = ? AND attivita_id = ? AND stato <> 'cancellata'");
        $stmt->execute([$_SESSION['user_id'], $attivita_id]);
        $ha_prenotato = $stmt->fetch() !== false;
    } elseif ($_SESSION['user_type'] === 'utente') {
        $stmt = $pdo->prepare("SELECT id FROM prenotazioni WHERE utente_id = ? AND attivita_id = ? AND stato <> 'cancellata'");
        $stmt->execute([$_SESSION['user_id'], $attivita_id]);
        $ha_prenotato = $stmt->fetch() !== false;
    }
}

$posti_disponibili = $attivita['max_partecipanti'] - $attivita['prenotazioni_count'];
$video_embed_url = !empty($attivita['materiali']) ? youtubeEmbedUrl($attivita['materiali']) : null;
$webxrUrl = availableActivityUrl(resolveWebxrUrl((string) $attivita['titolo'], $attivita['link_webxr'] ?? null));
$materialUrl = availableActivityUrl($attivita['materiali'] ?? null);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partecipa - <?= htmlspecialchars($attivita['titolo']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <?php if ($attivita['supporta_vr'] && $attivita['link_webxr']): ?>
        <script src="https://aframe.io/releases/1.4.0/aframe.min.js"></script>
    <?php endif; ?>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <?php if ($flash_success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash_success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($flash_error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash_error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2><?= htmlspecialchars($attivita['titolo']) ?></h2>
                        <p class="text-muted">
                            <i class="bi bi-building"></i> <?= htmlspecialchars($attivita['istituto_nome']) ?>
                        </p>
                        <hr>
                        <p><?= nl2br(htmlspecialchars($attivita['descrizione'])) ?></p>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <p><strong>Data e Ora:</strong><br>
                                <?= date('d/m/Y H:i', strtotime($attivita['data_ora'])) ?></p>
                                <p><strong>Durata:</strong><br><?= $attivita['durata_minuti'] ?> minuti</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Tipo:</strong><br><?= ucfirst(str_replace('_', ' ', $attivita['tipo_attivita'])) ?></p>
                                <?php if ($attivita['supporta_vr']): ?>
                                    <p><span class="badge bg-info"><i class="bi bi-vr"></i> Supporta VR</span></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($video_embed_url): ?>
                            <hr>
                            <h3 class="h5 mb-3"><i class="bi bi-play-btn-fill me-2"></i>Video dell’attività</h3>
                            <div class="ratio ratio-16x9 rounded overflow-hidden">
                                <iframe src="<?= htmlspecialchars($video_embed_url) ?>"
                                        title="Video: <?= htmlspecialchars($attivita['titolo']) ?>"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                            </div>
                            <a href="<?= htmlspecialchars($attivita['materiali']) ?>" class="btn btn-outline-primary btn-sm mt-3" target="_blank" rel="noopener noreferrer">
                                <i class="bi bi-youtube me-1"></i>Apri su YouTube
                            </a>
                        <?php endif; ?>

                        <?php if (!$video_embed_url && $materialUrl !== ''): ?>
                            <hr>
                            <a href="<?= htmlspecialchars($materialUrl) ?>" class="btn btn-outline-primary" target="_blank" rel="noopener noreferrer">Apri materiali dell’attività</a>
                        <?php endif; ?>

                        <?php if (isLoggedIn() && $_SESSION['user_type'] === 'utente' && $webxrUrl !== ''): ?>
                            <hr>
                            <div class="d-grid gap-2">
                                <?php
                                    // Per i laboratori di Chimica, forziamo il link verso Molecular WebXR
                                    if (!empty($attivita['titolo']) && stripos($attivita['titolo'], 'chimic') !== false) {
                                        $webxrUrl = 'https://molecularwebxr.org/app';
                                    }
                                ?>
                                <a href="<?= htmlspecialchars($webxrUrl) ?>" class="btn btn-success" target="_blank" rel="noopener noreferrer">
                                    <i class="bi bi-box-arrow-up-right"></i> Apri contenuto dell’attività
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 id="prenotazione">Prenotazione</h5>
                        <p><strong>Posti disponibili:</strong> <?= max(0, $posti_disponibili) ?>/<?= $attivita['max_partecipanti'] ?></p>

                        <?php if (isLoggedIn() && in_array($_SESSION['user_type'], ['utente', 'istituto', 'partner'], true)): ?>
                            <?php if ($ha_prenotato): ?>
                                <div class="alert alert-success">Hai già prenotato questa attività</div>
                                <?php if(strtotime($attivita['data_ora'])>time()): ?>
                                <form method="POST" action="prenotazione_cancella.php">
                                    <?= csrfField() ?><input type="hidden" name="attivita_id" value="<?= (int)$attivita_id ?>">
                                    <button class="btn btn-outline-danger" type="submit">Cancella prenotazione</button>
                                </form>
                                <?php endif; ?>
                                <a href="attivita_partecipa.php?id=<?= $attivita_id ?>&lang=<?= $lang ?>" class="btn btn-primary w-100">
                                    <i class="bi bi-box-arrow-in-right"></i> Partecipa
                                </a>
                            <?php elseif ($posti_disponibili > 0 && $attivita['stato'] === 'pubblicata' && strtotime($attivita['data_ora'])>time()): ?>
                                <form method="POST" action="prenota.php">
<?= csrfField() ?>
                                    <input type="hidden" name="attivita_id" value="<?= $attivita_id ?>">
                                    <input type="hidden" name="lang" value="<?= $lang ?>">
                                    <div class="mb-2">
                                        <label class="form-label small mb-1">Modalita di fruizione</label>
                                        <select aria-label="Modalità di fruizione" name="modalita_fruizione" class="form-select form-select-sm">
                                            <option value="casa">Da casa (WebXR)</option>
                                            <option value="arena_fisica">In Arena VR</option>
                                            <option value="arena_mobile">Arena Mobile</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small mb-1">Numero partecipanti</label>
                                        <input aria-label="Numero partecipanti" type="number" name="numero_partecipanti" class="form-control form-control-sm" min="1" max="<?= max(1,(int)$posti_disponibili) ?>" value="1">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small mb-1">Note</label>
                                        <textarea aria-label="Note prenotazione" name="note" class="form-control form-control-sm" rows="3" placeholder="Inserisci eventuali esigenze, classe, sezione o note organizzative"></textarea>
                                    </div>
                                    <label for="partner_vr_id" class="form-label">Struttura VR (solo arena)</label>
                                    <select id="partner_vr_id" name="partner_vr_id" class="form-select mb-3">
                                    <option value="0">Seleziona una struttura per le modalità arena</option>
                                    <?php foreach($pdo->query("SELECT ID_Ente,Ragione_Sociale,Tipologia FROM istituti_e_partner WHERE Stato_Validazione=1 AND Tipologia IN ('ARENA_VR','ARENA_MOBILE','PARTNER_VR') ORDER BY Ragione_Sociale") as $venue): ?>
                                    <option value="<?= (int)$venue['ID_Ente'] ?>"><?= htmlspecialchars($venue['Ragione_Sociale'].' — '.$venue['Tipologia']) ?></option>
                                    <?php endforeach; ?>
                                    </select><button type="submit" class="btn btn-primary w-100">Prenota</button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning">Non disponibile</div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted">Effettua il login per prenotare</p>
                            <a href="login.php?lang=<?= $lang ?>" class="btn btn-primary w-100">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($booking_popup)): ?>
        <div class="modal fade" id="bookingSuccessModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Prenotazione confermata</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2"><strong>Attività:</strong> <?= htmlspecialchars($booking_popup['titolo'] ?? $attivita['titolo']) ?></p>
                        <p class="mb-2"><strong>Organizzatore:</strong> <?= htmlspecialchars($booking_popup['organizzatore'] ?? $attivita['istituto_nome']) ?></p>
                        <p class="mb-2"><strong>Partecipanti:</strong> <?= htmlspecialchars((string)($booking_popup['numero_partecipanti'] ?? 1)) ?></p>
                        <p class="mb-2"><strong>Modalità:</strong> <?= htmlspecialchars($booking_popup['modalita'] ?? 'casa') ?></p>
                        <p class="mb-0"><strong>QR:</strong> <?= htmlspecialchars($booking_popup['qr_code'] ?? '') ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal">Chiudi</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (!empty($booking_popup)): ?>
        <script>
            const bookingSuccessModal = new bootstrap.Modal(document.getElementById('bookingSuccessModal'));
            bookingSuccessModal.show();
        </script>
    <?php endif; ?>
</body>
</html>
