<?php
require_once 'config.php';
requireRole(['istituto', 'partner']);

$lang = $_GET['lang'] ?? 'it';
$organizzatore_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $titolo = sanitize($_POST['titolo'] ?? '');
    $descrizione = sanitize($_POST['descrizione'] ?? '');
    $tipo_attivita = $_POST['tipo_attivita'] ?? '';
    $data_ora = $_POST['data_ora'] ?? '';
    $durata_minuti = intval($_POST['durata_minuti'] ?? 60);
    $max_partecipanti = intval($_POST['max_partecipanti'] ?? 50);
    $supporta_vr = isset($_POST['supporta_vr']) ? 1 : 0;
    $url_vr = trim($_POST['url_vr'] ?? '');
    $materiali_url = trim($_POST['materiali_url'] ?? '');
    $stato = 'bozza';

    if ($max_partecipanti<1 || $max_partecipanti>10000 || $durata_minuti<1 || $durata_minuti>1440 || !validActivityUrl($url_vr) || !validActivityUrl($materiali_url) || !in_array($tipo_attivita,['presentazione','laboratorio','tour_virtuale','open_day','workshop','altro'],true) || !strtotime($data_ora)) {
        $error='Controlla capienza, durata, data e indirizzi dei contenuti.';
    } elseif (empty($titolo) || empty($descrizione) || empty($tipo_attivita) || empty($data_ora)) {
        $error = 'Compila tutti i campi obbligatori';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO attivita_eventi
                (FK_Ente_Organizzatore, Titolo, Descrizione, Tipo_Attivita, Data_Ora, Durata_Minuti,
                 Max_Posti, Supporta_VR, Link_WebXR, Materiali_URL, Stato)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $organizzatore_id, $titolo, $descrizione, $tipo_attivita, $data_ora,
                $durata_minuti, $max_partecipanti, $supporta_vr, $url_vr ?: null, $materiali_url ?: null, $stato
            ]);
            $_SESSION['success'] = 'Evento inviato. Sarà pubblicato dopo l’approvazione dell’amministratore.';
            header('Location: attivita_gestione.php?lang=' . $lang);
            exit;
        } catch(PDOException $e) {
            $error = 'Errore durante la creazione: ';
        }
    }
}

$translations = [
    'it' => [
        'title' => 'Nuova Attività',
        'nuova_attivita' => 'Nuova Attività',
        'titolo' => 'Titolo *',
        'descrizione' => 'Descrizione *',
        'tipo_attivita' => 'Tipo Attività *',
        'data_ora' => 'Data e Ora *',
        'durata_minuti' => 'Durata (minuti)',
        'max_partecipanti' => 'Massimo Partecipanti',
        'supporta_vr' => 'Supporta VR',
        'url_vr' => 'URL Ambiente VR',
        'materiali_url' => 'URL Materiali',
        'stato' => 'Stato',
        'salva' => 'Invia per approvazione',
        'annulla' => 'Annulla'
    ],
    'en' => [
        'title' => 'New Activity',
        'nuova_attivita' => 'New Activity',
        'titolo' => 'Title *',
        'descrizione' => 'Description *',
        'tipo_attivita' => 'Activity Type *',
        'data_ora' => 'Date & Time *',
        'durata_minuti' => 'Duration (minutes)',
        'max_partecipanti' => 'Max Participants',
        'supporta_vr' => 'VR Supported',
        'url_vr' => 'VR Environment URL',
        'materiali_url' => 'Materials URL',
        'stato' => 'Status',
        'salva' => 'Submit for approval',
        'annulla' => 'Cancel'
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
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header">
                        <h4 class="mb-0"><?= $t['nuova_attivita'] ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>L’evento sarà salvato come “Da approvare” e diventerà pubblico solo dopo la verifica dell’amministratore.
                        </div>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>

                        <form method="POST">
<?= csrfField() ?>
                            <div class="mb-3">
                                <label for="titolo" class="form-label"><?= $t['titolo'] ?></label>
                                <input type="text" class="form-control" id="titolo" name="titolo" required>
                            </div>

                            <div class="mb-3">
                                <label for="descrizione" class="form-label"><?= $t['descrizione'] ?></label>
                                <textarea class="form-control" id="descrizione" name="descrizione" rows="5" required></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tipo_attivita" class="form-label"><?= $t['tipo_attivita'] ?></label>
                                    <select class="form-select" id="tipo_attivita" name="tipo_attivita" required>
                                        <option value="">-- Seleziona --</option>
                                        <option value="presentazione">Presentazione</option>
                                        <option value="laboratorio">Laboratorio</option>
                                        <option value="tour_virtuale">Tour Virtuale</option>
                                        <option value="open_day">Open Day</option>
                                        <option value="workshop">Workshop</option>
                                        <option value="altro">Altro</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="data_ora" class="form-label"><?= $t['data_ora'] ?></label>
                                    <input type="datetime-local" class="form-control" id="data_ora" name="data_ora" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="durata_minuti" class="form-label"><?= $t['durata_minuti'] ?></label>
                                    <input type="number" class="form-control" id="durata_minuti" name="durata_minuti" value="60" min="15" step="15">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="max_partecipanti" class="form-label"><?= $t['max_partecipanti'] ?></label>
                                    <input type="number" class="form-control" id="max_partecipanti" name="max_partecipanti" value="50" min="1">
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="supporta_vr" name="supporta_vr" onchange="toggleVRFields()">
                                    <label class="form-check-label" for="supporta_vr">
                                        <?= $t['supporta_vr'] ?>
                                    </label>
                                </div>
                            </div>

                            <div id="vrFields" style="display: none;">
                                <div class="mb-3">
                                    <label for="url_vr" class="form-label"><?= $t['url_vr'] ?></label>
                                    <input type="url" class="form-control" id="url_vr" name="url_vr" placeholder="https://...">
                                    <small class="form-text text-muted">URL dell'ambiente VR (es. A-Frame scene)</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="materiali_url" class="form-label"><?= $t['materiali_url'] ?></label>
                                <input type="url" class="form-control" id="materiali_url" name="materiali_url" placeholder="https://...">
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><?= $t['salva'] ?></button>
                                <a href="attivita_gestione.php?lang=<?= $lang ?>" class="btn btn-secondary"><?= $t['annulla'] ?></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleVRFields() {
            const checkbox = document.getElementById('supporta_vr');
            const fields = document.getElementById('vrFields');
            fields.style.display = checkbox.checked ? 'block' : 'none';
        }
    </script>
</body>
</html>
