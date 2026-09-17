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

<style>
    #chiSiamoModal { z-index: 10050; }
    .modal-backdrop.show { z-index: 10040; }
    #chiSiamoModal .modal-dialog {
        height: calc(100vh - 1rem);
        max-height: calc(100vh - 1rem);
        margin: .5rem auto;
    }
    #chiSiamoModal .modal-content { max-height: 100%; overflow: hidden; }
    #chiSiamoModal .modal-header,
    #chiSiamoModal .modal-footer,
    #chiSiamoModal .chi-siamo-media { flex: 0 0 auto; }
    #chiSiamoModal .modal-body {
        display: flex;
        flex-direction: column;
        min-height: 0;
        overflow: hidden;
        padding: 0;
    }
    #chiSiamoModal .modal-header {
        padding: .4rem .75rem;
    }
    #chiSiamoModal .modal-title {
        font-size: 1rem;
        line-height: 1.2;
    }
    #chiSiamoModal .modal-header .btn-close {
        margin: 0;
        padding: .45rem;
    }
    #chiSiamoModal .modal-footer {
        padding: .35rem .75rem;
    }
    #chiSiamoModal .modal-footer .btn {
        padding: .25rem .65rem;
        font-size: .875rem;
    }
    #chiSiamoModal .chi-siamo-image {
        display: block;
        width: 85%;
        height: auto;
        margin: 0 auto;
    }
    #chiSiamoModal .chi-siamo-text {
        min-height: 0;
        overflow-y: auto;
        padding: .75rem 1rem 1rem;
        line-height: 1.5;
    }
    #chiSiamoModal .chi-siamo-text h4,
    #chiSiamoModal .chi-siamo-text h5 {
        color: #212529;
        font-weight: 700;
    }
    #chiSiamoModal .chi-siamo-text blockquote {
        margin: 1rem 0 0;
        padding: .75rem 1rem;
        border-left: 4px solid #0d6efd;
        background: #f5f8fc;
        color: #24364b;
        font-weight: 600;
    }
</style>

<div class="modal fade" id="chiSiamoModal" tabindex="-1" aria-labelledby="chiSiamoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chiSiamoModalLabel"><?= htmlspecialchars($nt['chi_siamo_title']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars($nt['chiudi']) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="chi-siamo-media">
                    <img src="image/chi-siamo.webp"
                         onerror="this.onerror=null;this.src='https://raw.githubusercontent.com/GGrosu74/Open_House/main/image/chi-siamo.webp';"
                         alt="Studenti in un ambiente formativo con realtà virtuale e robotica"
                         class="img-fluid chi-siamo-image" width="1792" height="1024">
                </div>
                <div class="chi-siamo-text text-muted">
                    <h4>CHI SIAMO</h4>
                    <h5>Benvenuti in VR Open House</h5>
                    <p><strong>VR Open House</strong> è la piattaforma di orientamento immersivo creata per guidare i giovani nella scelta del proprio percorso scolastico e professionale.</p>
                    <p>Nata come progetto innovativo presso l’<strong>ITIS Pietro Paleocapa</strong>, la nostra missione è trasformare l'orientamento in un'esperienza reale, moderna e coinvolgente, superando i limiti delle tradizionali giornate di porte aperte.</p>

                    <h5>La Nostra Visione</h5>
                    <p>Crediamo che ogni studente debba avere l'opportunità di esplorare il proprio futuro senza barriere. Attraverso la tecnologia <strong>WebXR Open Source</strong>, colleghiamo studenti, scuole, università e aziende all'interno di un unico Hub virtuale interattivo.</p>

                    <h5>I Nostri Tre Pilastri</h5>
                    <ul>
                        <li class="mb-2">🌐 <strong>Accessibilità Totale:</strong> Entra in laboratori 3D e ambienti scolastici da qualsiasi dispositivo (PC, smartphone o visore VR), direttamente dal tuo browser e senza installare alcuna app.</li>
                        <li class="mb-2">📍 <strong>Inclusione sul Territorio:</strong> Abbattiamo il digital divide grazie alla rete di <strong>Arene VR e Arene Mobile</strong>, portando l'hardware di ultima generazione anche a chi non possiede un visore a casa.</li>
                        <li>📜 <strong>Formazione Certificata:</strong> Offriamo percorsi trasparenti e sicuri che tracciano le presenze per la certificazione delle ore di Formazione Scuola-Lavoro (FSL).</li>
                    </ul>

                    <blockquote>“Un ponte tra scuola, tecnologia e mondo del lavoro per rendere l'orientamento e la formazione un'opportunità aperta a tutti.”</blockquote>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars($nt['chiudi']) ?></button>
            </div>
        </div>
    </div>
</div>
