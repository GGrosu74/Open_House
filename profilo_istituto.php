<?php
require_once 'config.php';
requireIstituto();

$lang = $_GET['lang'] ?? 'it';
$istituto_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Carica dati istituto
$stmt = $pdo->prepare("SELECT *, ID_Ente as id, Ragione_Sociale as nome, Tipologia as tipo_scuola, Cod_Mecc as codice_istituto FROM istituti_e_partner WHERE ID_Ente = ?");
$stmt->execute([$istituto_id]);
$istituto = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome'] ?? '');
    $tipo_scuola = $_POST['tipo_scuola'] ?? '';
    $indirizzo = sanitize($_POST['indirizzo'] ?? '');
    $regione = sanitize($_POST['regione'] ?? '');
    $provincia = sanitize($_POST['provincia'] ?? '');
    $telefono = sanitize($_POST['telefono'] ?? '');
    $descrizione = sanitize($_POST['descrizione'] ?? '');

    if (empty($nome) || empty($tipo_scuola)) {
        $error = 'Compila tutti i campi obbligatori';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE istituti_e_partner SET Ragione_Sociale = ?, indirizzo = ?, regione = ?, provincia = ? WHERE ID_Ente = ?");
            $stmt->execute([$nome, $indirizzo, $regione ?: null, $provincia ?: null, $istituto_id]);
            $success = 'Profilo aggiornato con successo!';
            $_SESSION['user_name'] = $nome;
            header('Location: profilo_istituto.php?lang=' . $lang);
            exit;
        } catch(PDOException $e) {
            $error = 'Errore durante l\'aggiornamento';
        }
    }

    // Ricarica dati
    $stmt = $pdo->prepare("SELECT *, ID_Ente as id, Ragione_Sociale as nome, Tipologia as tipo_scuola, Cod_Mecc as codice_istituto FROM istituti_e_partner WHERE ID_Ente = ?");
    $stmt->execute([$istituto_id]);
    $istituto = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo Istituto</title>
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
                        <h4 class="mb-0">Profilo Istituto</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>

                        <form method="POST">
<?= csrfField() ?>
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Istituto *</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($istituto['nome']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($istituto['email']) ?>" disabled>
                                <small class="form-text text-muted">L'email non può essere modificata</small>
                            </div>

                            <div class="mb-3">
                                <label for="tipo_scuola" class="form-label">Tipo Scuola *</label>
                                <select class="form-select" id="tipo_scuola" name="tipo_scuola" required>
                                    <option value="infanzia" <?= $istituto['tipo_scuola'] === 'infanzia' ? 'selected' : '' ?>>Scuola dell'Infanzia</option>
                                    <option value="primaria" <?= $istituto['tipo_scuola'] === 'primaria' ? 'selected' : '' ?>>Scuola Primaria</option>
                                    <option value="secondaria_primo" <?= $istituto['tipo_scuola'] === 'secondaria_primo' ? 'selected' : '' ?>>Scuola Secondaria di Primo Grado</option>
                                    <option value="secondaria_secondo" <?= $istituto['tipo_scuola'] === 'secondaria_secondo' ? 'selected' : '' ?>>Scuola Secondaria di Secondo Grado</option>
                                    <option value="universita" <?= $istituto['tipo_scuola'] === 'universita' ? 'selected' : '' ?>>Università</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="indirizzo" class="form-label">Indirizzo</label>
                                <textarea class="form-control" id="indirizzo" name="indirizzo" rows="2"><?= htmlspecialchars($istituto['indirizzo'] ?? '') ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="provincia" class="form-label">Provincia</label>
                                    <input type="text" class="form-control" id="provincia" name="provincia"
                                           value="<?= htmlspecialchars($istituto['provincia'] ?? '') ?>"
                                           placeholder="Es. Milano">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="regione" class="form-label">Regione</label>
                                    <input type="text" class="form-control" id="regione" name="regione"
                                           value="<?= htmlspecialchars($istituto['regione'] ?? '') ?>"
                                           placeholder="Es. Lombardia">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="telefono" class="form-label">Telefono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" value="<?= htmlspecialchars($istituto['telefono'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="descrizione" class="form-label">Descrizione</label>
                                <textarea class="form-control" id="descrizione" name="descrizione" rows="5"><?= htmlspecialchars($istituto['descrizione'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                            <a href="dashboard_istituto.php?lang=<?= $lang ?>" class="btn btn-secondary">Annulla</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
