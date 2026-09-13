<?php
require_once 'config.php';

$lang = $_GET['lang'] ?? 'it';
$isEnglish = $lang === 'en';
$courses = [
    [
        'provider' => 'Accademia Domani',
        'title' => $isEnglish ? 'Virtual, Augmented and Mixed Reality certified online course' : 'Corso online certificato: Realtà Virtuale, Aumentata e Mista',
        'description' => $isEnglish ? 'Online training pathway dedicated to immersive technologies.' : 'Percorso online dedicato alle tecnologie immersive e alle loro applicazioni.',
        'url' => 'https://www.accademiadomani.it/programmi-dei-corsi/item/corso-online-certificato-realta-virtuale-aumentata-mista/',
        'icon' => 'bi-mortarboard-fill',
    ],
    [
        'provider' => 'IDEGO Psicologia Digitale',
        'title' => $isEnglish ? 'Digital Psychology Masterclass' : 'Masterclass di Psicologia Digitale',
        'description' => $isEnglish ? 'Live online training on the use of virtual reality in psychological practice.' : 'Formazione online dal vivo sull’uso della realtà virtuale nella pratica psicologica.',
        'url' => 'https://www.idego.it/prodotto/prima-rata-masterclass/',
        'icon' => 'bi-vr',
    ],
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEnglish ? 'Courses and Certifications' : 'Corsi e Certificazioni' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <main class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="h2 mb-2"><?= $isEnglish ? 'Courses and Certifications' : 'Corsi e Certificazioni' ?></h1>
                <p class="text-muted mb-0"><?= $isEnglish ? 'External training opportunities selected for immersive technologies.' : 'Opportunità formative esterne dedicate alle tecnologie immersive.' ?></p>
            </div>
            <a href="index.php?lang=<?= $lang ?>#ecosistema" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i><?= $isEnglish ? 'Back to Experiences' : 'Torna alle Esperienze' ?>
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($courses as $course): ?>
                <div class="col-md-6">
                    <article class="card h-100 shadow-sm border-0">
                        <div class="card-body p-4">
                            <i class="bi <?= htmlspecialchars($course['icon']) ?> display-5 text-primary"></i>
                            <p class="text-uppercase small fw-semibold text-muted mt-3 mb-1"><?= htmlspecialchars($course['provider']) ?></p>
                            <h2 class="h4"><?= htmlspecialchars($course['title']) ?></h2>
                            <p class="text-muted"><?= htmlspecialchars($course['description']) ?></p>
                            <a href="<?= htmlspecialchars($course['url']) ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
                                <?= $isEnglish ? 'Visit course website' : 'Visita il sito del corso' ?> <i class="bi bi-box-arrow-up-right ms-1"></i>
                            </a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="alert alert-info mt-4 mb-0" role="note">
            <?= $isEnglish ? 'These courses are offered by external providers. Check their websites for current dates, requirements and prices.' : 'I corsi sono offerti da enti esterni. Verifica sui rispettivi siti date, requisiti e prezzi aggiornati.' ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
