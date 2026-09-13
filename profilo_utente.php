<?php
require_once 'config.php';
requireUtente();

$lang = $_GET['lang'] ?? 'it';
$utente_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Carica dati utente
try {
    $userTable = getUserTable($pdo);
    $stmt = $pdo->prepare("SELECT * FROM {$userTable} WHERE id = ?");
    $stmt->execute([$utente_id]);
    $utente = $stmt->fetch();
    if (!$utente) {
        $error = 'Utente non trovato.';
        $utente = [
            'nome' => '',
            'cognome' => '',
            'email' => '',
            'tipo_utente' => '',
            'data_nascita' => '',
            'telefono' => ''
        ];
    }
} catch (RuntimeException $e) {
    $error = $e->getMessage();
    $utente = [
        'nome' => '',
        'cognome' => '',
        'email' => '',
        'tipo_utente' => '',
        'data_nascita' => '',
        'telefono' => ''
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome'] ?? '');
    $cognome = sanitize($_POST['cognome'] ?? '');
    $tipo_utente = $_POST['tipo_utente'] ?? '';
    $data_nascita = $_POST['data_nascita'] ?? '';
    $telefono = sanitize($_POST['telefono'] ?? '');

    if (empty($nome) || empty($cognome) || empty($tipo_utente)) {
        $error = 'Compila tutti i campi obbligatori';
    } else {
        try {
            $userTable = getUserTable($pdo);
            $stmt = $pdo->prepare("UPDATE {$userTable} SET nome = ?, cognome = ?, tipo_utente = ?, data_nascita = ?, telefono = ? WHERE id = ?");
            $stmt->execute([$nome, $cognome, $tipo_utente, $data_nascita ?: null, $telefono, $utente_id]);
            $success = 'Profilo aggiornato con successo!';
            $_SESSION['user_name'] = $nome . ' ' . $cognome;
            header('Location: profilo_utente.php?lang=' . $lang);
            exit;
        } catch(PDOException $e) {
            $error = 'Errore durante l\'aggiornamento';
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }

    // Ricarica dati
    if (empty($error)) {
        $userTable = getUserTable($pdo);
        $stmt = $pdo->prepare("SELECT * FROM {$userTable} WHERE id = ?");
        $stmt->execute([$utente_id]);
        $utente = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo Utente</title>
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
                        <h4 class="mb-0">Profilo Utente</h4>
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
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nome" class="form-label">Nome *</label>
                                    <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($utente['nome']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cognome" class="form-label">Cognome *</label>
                                    <input type="text" class="form-control" id="cognome" name="cognome" value="<?= htmlspecialchars($utente['cognome']) ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($utente['email']) ?>" disabled>
                                <small class="form-text text-muted">L'email non può essere modificata</small>
                            </div>

                            <div class="mb-3">
                                <label for="tipo_utente" class="form-label">Tipo Utente *</label>
                                <select class="form-select" id="tipo_utente" name="tipo_utente" required>
                                    <option value="studente" <?= $utente['tipo_utente'] === 'studente' ? 'selected' : '' ?>>Studente</option>
                                    <option value="genitore" <?= $utente['tipo_utente'] === 'genitore' ? 'selected' : '' ?>>Genitore</option>
                                    <option value="docente" <?= $utente['tipo_utente'] === 'docente' ? 'selected' : '' ?>>Docente</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="data_nascita" class="form-label">Data di Nascita</label>
                                <input type="date" class="form-control" id="data_nascita" name="data_nascita" value="<?= $utente['data_nascita'] ?>">
                            </div>

                            <div class="mb-3">
                                <label for="telefono" class="form-label">Telefono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" value="<?= htmlspecialchars($utente['telefono']) ?>">
                            </div>

                            <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                            <a href="dashboard_utente.php?lang=<?= $lang ?>" class="btn btn-secondary">Annulla</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
