<?php

require_once __DIR__ . '/activity_media.php';

function initializeSecurity(PDO $pdo): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function csrfField(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrfToken(?string $token = null): bool {
    $token ??= $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    return session_status() === PHP_SESSION_ACTIVE
        && !empty($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function loginRateAllowed(): bool {
    $now = time();
    $window = 900;
    $maxAttempts = 10;
    $attempts = $_SESSION['login_attempts'] ?? [];

    $attempts = array_values(array_filter(
        $attempts,
        static fn ($timestamp): bool => is_int($timestamp) && ($now - $timestamp) < $window
    ));

    $_SESSION['login_attempts'] = $attempts;
    return count($attempts) < $maxAttempts;
}

function requireCsrf(): void {
    if (!verifyCsrfToken()) {
        http_response_code(403);
        exit('Sessione scaduta o richiesta non valida. Ricarica la pagina e riprova.');
    }
}

function enteRole(array $ente): string {
    return !empty($ente['Cod_Mecc']) ? 'istituto' : 'partner';
}

function paginationLinks(int $page, bool $hasMore): string {
    if ($page <= 1 && !$hasMore) return '';
    $html = '<nav aria-label="Pagine dei risultati" class="d-flex gap-2">';
    foreach (['Precedente' => $page - 1, 'Successiva' => $page + 1] as $label => $target) {
        if ($target < 1 || ($target > $page && !$hasMore)) continue;
        $query = $_GET;
        $query['page'] = $target;
        $html .= '<a class="btn btn-outline-primary" href="?' . htmlspecialchars(http_build_query($query), ENT_QUOTES, 'UTF-8') . '">' . $label . '</a>';
    }
    return $html . '</nav>';
}

/** Accesso riservato a organizzatore, amministratore e prenotanti confermati. */
function requireActivityAccess(PDO $pdo, int $activityId): void {
    requireLogin();
    $stmt = $pdo->prepare('SELECT FK_Ente_Organizzatore, Stato FROM attivita_eventi WHERE ID_Attivita = ?');
    $stmt->execute([$activityId]);
    $activity = $stmt->fetch();
    if (!$activity) {
        http_response_code(404);
        exit('Attività non trovata.');
    }
    $role = $_SESSION['user_type'];
    $userId = (int) $_SESSION['user_id'];
    if ($role === 'admin' || (in_array($role, ['istituto', 'partner'], true)
        && $userId === (int) $activity['FK_Ente_Organizzatore'])) {
        return;
    }
    if (in_array($role, ['utente', 'istituto', 'partner'], true)
        && in_array($activity['Stato'], ['pubblicata', 'in_corso', 'completata'], true)) {
        $column = $role === 'utente' ? 'utente_id' : 'istituto_prenotante_id';
        $stmt = $pdo->prepare("SELECT id FROM prenotazioni WHERE attivita_id = ? AND {$column} = ? AND stato IN ('confermata', 'completata') LIMIT 1");
        $stmt->execute([$activityId, $userId]);
        if ($stmt->fetch()) {
            return;
        }
    }
    http_response_code(403);
    exit('Per partecipare è necessaria una prenotazione confermata.');
}
