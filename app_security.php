<?php

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
