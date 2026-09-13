<?php
require_once 'config.php';
requireAdmin();

$lang = $_GET['lang'] ?? $_POST['lang'] ?? 'it';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT password FROM admin WHERE id = ?');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $currentHash = $stmt->fetchColumn();

    if (!$currentHash || !verifyPassword($currentPassword, $currentHash)) {
        $error = 'La password attuale non è corretta.';
    } elseif (strlen($newPassword) < 12) {
        $error = 'La nuova password deve contenere almeno 12 caratteri.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Le nuove password non corrispondono.';
    } elseif (hash_equals($currentPassword, $newPassword)) {
        $error = 'La nuova password deve essere diversa da quella temporanea.';
    } else {
        $stmt = $pdo->prepare('UPDATE admin SET password = ?, must_change_password = 0 WHERE id = ?');
        $stmt->execute([hashPassword($newPassword), (int)$_SESSION['user_id']]);
        $_SESSION['must_change_password'] = 0;
        session_regenerate_id(true);
        header('Location: dashboard_admin.php?lang=' . urlencode($lang));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambia password amministratore - VR Open House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <main class="container d-flex align-items-center justify-content-center py-5">
        <div class="card shadow-sm" style="max-width: 560px; width: 100%;">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock text-primary" style="font-size: 3rem;"></i>
                    <h1 class="h3 mt-2">Cambia la password amministratore</h1>
                    <?php if (!empty($_SESSION['must_change_password'])): ?>
                        <p class="text-muted mb-0">Per continuare, sostituisci la password temporanea.</p>
                    <?php endif; ?>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password attuale</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nuova password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="12" autocomplete="new-password" required>
                        <div class="form-text">Usa almeno 12 caratteri.</div>
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Conferma nuova password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="12" autocomplete="new-password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check2-circle me-2"></i>Salva nuova password
                    </button>
                </form>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
