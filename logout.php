<?php
require_once 'config.php';

$lang = $_GET['lang'] ?? $_POST['lang'] ?? 'it';
$lang = in_array($lang, ['it', 'en'], true) ? $lang : 'it';

$translations = [
    'it' => [
        'title' => 'Logout - VR Open House',
        'heading' => 'Vuoi uscire?',
        'message' => 'La sessione verrà chiusa in modo sicuro. Potrai accedere nuovamente in qualsiasi momento.',
        'confirm' => 'Esci dal sito',
        'cancel' => 'Torna alla dashboard',
    ],
    'en' => [
        'title' => 'Logout - VR Open House',
        'heading' => 'Do you want to log out?',
        'message' => 'Your session will be closed securely. You can log in again at any time.',
        'confirm' => 'Log out',
        'cancel' => 'Back to dashboard',
    ],
];
$t = $translations[$lang];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
    header('Location: index.php?lang=' . urlencode($lang));
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php $active_page = ''; include 'navbar.php'; ?>

    <main class="container d-flex align-items-center justify-content-center py-5">
        <div class="card shadow-sm text-center" style="max-width: 540px; width: 100%;">
            <div class="card-body p-4 p-md-5">
                <div class="text-primary mb-3" aria-hidden="true">
                    <i class="bi bi-box-arrow-right" style="font-size: 3rem;"></i>
                </div>
                <h1 class="h2 mb-3"><?= htmlspecialchars($t['heading']) ?></h1>
                <p class="text-muted mb-4"><?= htmlspecialchars($t['message']) ?></p>

                <form method="POST" class="d-grid gap-3">
                    <?= csrfField() ?>
                    <input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-right me-2"></i><?= htmlspecialchars($t['confirm']) ?>
                    </button>
                    <a href="dashboard.php?lang=<?= htmlspecialchars($lang) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i><?= htmlspecialchars($t['cancel']) ?>
                    </a>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
