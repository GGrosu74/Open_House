<?php
require_once 'config.php';
requireLogin();

$lang = $_GET['lang'] ?? 'it';
$attivita_id = $_GET['id'] ?? 0;

requireActivityAccess($pdo,(int)$attivita_id);

$stmt = $pdo->prepare("SELECT a.ID_Attivita as id, a.Titolo as titolo, a.Descrizione as descrizione, a.Data_Ora as data_ora,
                       a.Supporta_VR as supporta_vr, a.Max_Posti as max_partecipanti, a.Stato as stato,
                       a.Link_WebXR as url_vr, a.Materiali_URL as materiali_url,
                       i.Ragione_Sociale as istituto_nome FROM attivita_eventi a
                       JOIN istituti_e_partner i ON a.FK_Ente_Organizzatore = i.ID_Ente
                       WHERE a.ID_Attivita = ?");
$stmt->execute([$attivita_id]);
$attivita = $stmt->fetch();

if (!$attivita) {
    header('Location: index.php');
    exit;
}
$video_embed_url = !empty($attivita['materiali_url']) ? youtubeEmbedUrl($attivita['materiali_url']) : null;
$webxrUrl = availableActivityUrl(resolveWebxrUrl((string) $attivita['titolo'], $attivita['url_vr'] ?? null));
$materialUrl = availableActivityUrl($attivita['materiali_url'] ?? null);
$embedActivity = $attivita['supporta_vr'] && canEmbedActivityUrl($webxrUrl);

// Carica messaggi chat
$userTable = getUserTable($pdo, false);
$userJoin = $userTable ? "LEFT JOIN {$userTable} u ON m.utente_id = u.id" : '';
$userSelect = $userTable ? 'u.nome as utente_nome' : 'NULL as utente_nome';

$stmt = $pdo->prepare("SELECT m.*, {$userSelect}, i.Ragione_Sociale as istituto_nome
                       FROM messaggi_chat m
                       {$userJoin}
                       LEFT JOIN istituti_e_partner i ON m.istituto_id = i.ID_Ente
                       WHERE m.attivita_id = ?
                       ORDER BY m.created_at ASC");
$stmt->execute([$attivita_id]);
$messaggi = $stmt->fetchAll();
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
    <style>
        #chatModal { z-index: 10050; }
        .modal-backdrop { z-index: 10040; }
        #chatMessages { min-height: 280px; max-height: 55vh; overflow-y: auto; }
    </style>
</head>
<body class="bg-dark text-white">
    <?php include 'navbar.php'; ?>

    <div class="container-fluid mt-3 d-flex justify-content-between align-items-center gap-2">
        <a href="attivita_dettaglio.php?id=<?= $attivita_id ?>&lang=<?= $lang ?>" class="btn btn-outline-light btn-sm">Esci</a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#chatModal">
            <i class="bi bi-chat-dots-fill me-2"></i>Chat e Q&amp;A
        </button>
    </div>

    <div class="container-fluid mt-3">
        <?php if ($webxrUrl !== ''): ?>
            <div class="mb-3">
                <a href="<?= htmlspecialchars($webxrUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light">Apri contenuto in una nuova scheda</a>
                <?php if ($embedActivity): ?><span class="small text-white-50 ms-2">Se il contenuto non si carica qui, aprilo in una nuova scheda.</span><?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($materialUrl !== '' && !$video_embed_url): ?>
            <p><a class="btn btn-outline-info" href="<?= htmlspecialchars($materialUrl) ?>" target="_blank" rel="noopener noreferrer">Apri materiali dell’attività</a></p>
        <?php endif; ?>
        <div class="row">
            <?php if ($embedActivity): ?>
                <div class="col-12">
                    <div class="card bg-dark border-secondary">
                        <div class="card-body p-0" style="height: 80vh;">
                            <iframe title="Esperienza immersiva" allow="xr-spatial-tracking; fullscreen" allowfullscreen src="<?= htmlspecialchars($webxrUrl) ?>"
                                    style="width: 100%; height: 100%; border: none;"></iframe>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-12">
                    <div class="card bg-dark border-secondary">
                        <div class="card-body text-center p-5">
                            <h3><?= htmlspecialchars($attivita['titolo']) ?></h3>
                            <p class="lead"><?= htmlspecialchars($attivita['descrizione']) ?></p>
                            <?php if ($video_embed_url): ?>
                                <div class="ratio ratio-16x9 mt-4">
                                    <iframe src="<?= htmlspecialchars($video_embed_url) ?>"
                                            title="Video: <?= htmlspecialchars($attivita['titolo']) ?>"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                            referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                                </div>
                                <a href="<?= htmlspecialchars($attivita['materiali_url']) ?>" class="btn btn-outline-light mt-3" target="_blank" rel="noopener noreferrer">
                                    <i class="bi bi-youtube me-1"></i>Apri su YouTube
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content bg-dark text-white border-secondary">
                <div class="modal-header border-secondary">
                    <h2 class="modal-title h5" id="chatModalLabel"><i class="bi bi-chat-dots-fill me-2"></i>Chat e Q&amp;A</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body" id="chatMessages" aria-live="polite">
                    <?php foreach ($messaggi as $msg): ?>
                        <div class="mb-3">
                            <small class="text-white-50">
                                <?= htmlspecialchars($msg['utente_nome'] ?: $msg['istituto_nome']) ?> ·
                                <?= date('H:i', strtotime($msg['created_at'])) ?>
                            </small>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($msg['messaggio'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer border-secondary d-block">
                    <form id="chatForm" method="POST" action="chat_invia.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="attivita_id" value="<?= $attivita_id ?>">
                        <div class="input-group">
                            <input type="text" class="form-control" name="messaggio" id="messaggio" maxlength="2000" placeholder="Scrivi un messaggio o una domanda..." autocomplete="off" required>
                            <button type="submit" class="btn btn-primary" aria-label="Invia messaggio">
                                <i class="bi bi-send"></i>
                            </button>
                        </div>
                    </form>
                    <button type="button" class="btn btn-outline-light btn-sm mt-3" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const chatMessages = document.getElementById('chatMessages');
        const chatModal = document.getElementById('chatModal');
        const chatForm = document.getElementById('chatForm');

        function refreshChat() {
            fetch('chat_messaggi.php?attivita_id=<?= $attivita_id ?>')
                .then(response => response.json())
                .then(data => {
                    chatMessages.innerHTML = '';
                    data.forEach(msg => {
                        const div = document.createElement('div');
                        div.className = 'mb-2';
                        div.innerHTML = `
                            <small class="text-white-50">${msg.nome} · ${msg.time}</small>
                            <p class="mb-0">${msg.messaggio.replace(/\n/g, '<br>')}</p>
                        `;
                        chatMessages.appendChild(div);
                    });
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                })
                .catch(() => {});
        }

        chatModal.addEventListener('shown.bs.modal', function() {
            refreshChat();
            document.getElementById('messaggio').focus();
        });

        chatForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const submitButton = chatForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            fetch(chatForm.action, {
                method: 'POST',
                body: new FormData(chatForm),
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(response => {
                if (!response.ok) throw new Error('Invio non riuscito');
                chatForm.reset();
                refreshChat();
            }).catch(() => {
                alert('Non è stato possibile inviare il messaggio. Riprova.');
            }).finally(() => {
                submitButton.disabled = false;
                document.getElementById('messaggio').focus();
            });
        });

        setInterval(function() {
            if (chatModal.classList.contains('show')) refreshChat();
        }, 3000);
    </script>
</body>
</html>
