<?php if (realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__) { http_response_code(404); exit; } ?>
<?php
if (!isset($lang)) {
    $lang = $_GET['lang'] ?? 'it';
}
if (!isset($user_type)) {
    $user_type = $_SESSION['user_type'] ?? '';
}
if (!isset($active_page)) {
    $active_page = '';
}

$nav_translations = [
    'it' => [
        'brand' => 'VR Open House',
        'home' => 'Home',
        'istituti' => 'Istituti',
        'attivita' => 'Attività',
        'partner' => 'Partner',
        'chi_siamo' => 'Chi siamo',
        'dashboard' => 'Dashboard',
        'accedi' => 'Accedi',
        'registrati' => 'Registrati',
        'logout' => 'Logout',
        'chi_siamo_title' => 'Chi Siamo - VR Open House',
        'chiudi' => 'Chiudi',
    ],
    'en' => [
        'brand' => 'VR Open House',
        'home' => 'Home',
        'istituti' => 'Institutions',
        'attivita' => 'Activities',
        'partner' => 'Partners',
        'chi_siamo' => 'About us',
        'dashboard' => 'Dashboard',
        'accedi' => 'Login',
        'registrati' => 'Register',
        'logout' => 'Logout',
        'chi_siamo_title' => 'About Us - VR Open House',
        'chiudi' => 'Close',
    ],
];

$nt = $nav_translations[$lang] ?? $nav_translations['it'];
?>
<style>
    .navbar {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        z-index: 9999 !important;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var nav = document.querySelector('nav.navbar');
        if (nav) {
            document.body.style.paddingTop = nav.offsetHeight + 'px';
        }
    });
</script>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary site-header" style="background-color:#003366 !important;background:#003366 !important;">
    <div class="container-fluid px-3">
        <a class="navbar-brand me-4" href="index.php?lang=<?= htmlspecialchars($lang) ?>">
            <i class="bi bi-mortarboard"></i> <?= htmlspecialchars($nt['brand']) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link<?= $active_page === 'home' ? ' active' : '' ?>" href="index.php?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($nt['home']) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $active_page === 'istituti' ? ' active' : '' ?>" href="istituti_elenco.php?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($nt['istituti']) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $active_page === 'attivita' ? ' active' : '' ?>" href="attivita_elenco.php?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($nt['attivita']) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $active_page === 'partner' ? ' active' : '' ?>" href="partner_istituti.php?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($nt['partner']) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $active_page === 'chi_siamo' ? ' active' : '' ?>" href="#" data-bs-toggle="modal" data-bs-target="#chiSiamoModal"><?= htmlspecialchars($nt['chi_siamo']) ?></a>
                </li>
                <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($nt['dashboard']) ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($nt['logout']) ?></a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $active_page === 'login' ? ' active' : '' ?>" href="login.php?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($nt['accedi']) ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $active_page === 'register' ? ' active' : '' ?>" href="register.php?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($nt['registrati']) ?></a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="<?= basename($_SERVER['PHP_SELF']) ?>?lang=it" class="btn btn-sm btn-outline-light ms-lg-2<?= $lang === 'it' ? ' active' : '' ?>">IT</a>
                </li>
                <li class="nav-item">
                    <a href="<?= basename($_SERVER['PHP_SELF']) ?>?lang=en" class="btn btn-sm btn-outline-light ms-lg-2<?= $lang === 'en' ? ' active' : '' ?>">EN</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="modal fade" id="chiSiamoModal" tabindex="-1" aria-labelledby="chiSiamoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chiSiamoModalLabel"><?= htmlspecialchars($nt['chi_siamo_title']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars($nt['chiudi']) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 mb-3">
                        <img src="image/745d5f52-0e02-42ee-b3f5-1a39e2aa9f9a.webp" alt="VR Open House" class="img-fluid" style="max-height:300px; object-fit:cover; width:100%;">
                    </div>
                    <div class="col-12 text-muted" style="line-height:1.5;">
                        <h6><strong>L'innovazione al servizio dell'orientamento scolastico e della formazione</strong></h6>
                        <p>La piattaforma offre un ecosistema digitale intuitivo che consente agli istituti di superare i limiti della presenza fisica, offrendo visite virtuali, attività interattive e strumenti per l'orientamento.</p>
                        <p>Inclusività, accessibilità e visibilità per gli istituti sono i pilastri del progetto.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars($nt['chiudi']) ?></button>
            </div>
        </div>
    </div>
</div>
