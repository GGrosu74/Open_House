<?php

/** Consente URL web e percorsi relativi, escludendo schemi eseguibili e traversal. */
function validActivityUrl(?string $url): bool {
    $url = trim((string) $url);
    if ($url === '') return true;
    $decoded = rawurldecode($url);
    if (preg_match('/[\x00-\x1f\x7f\\\\]/', $decoded)) return false;
    $parts = parse_url($url);
    if ($parts === false || isset($parts['user']) || isset($parts['pass'])) return false;
    if (isset($parts['scheme'])) {
        return in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    if (isset($parts['host']) || str_starts_with($decoded, '/') || str_contains($decoded, ':')) return false;
    return !preg_match('~(^|/)\.\.(/|$)~', $decoded);
}

/** Evita pulsanti verso file locali mancanti anche dopo il caricamento su hosting. */
function availableActivityUrl(?string $url): string {
    $url = trim((string) $url);
    if ($url === '' || !validActivityUrl($url)) return '';
    if (parse_url($url, PHP_URL_SCHEME)) return $url;
    $path = rawurldecode(parse_url($url, PHP_URL_PATH) ?? '');
    return $path !== '' && is_file(__DIR__ . '/' . $path) ? $url : '';
}

function canEmbedActivityUrl(string $url): bool {
    $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
    // Le pagine dello store richiedono l'apertura diretta, non sono simulazioni WebXR.
    return $url !== '' && !in_array($host, ['store.steampowered.com', 'www.youtube.com', 'youtube.com', 'youtu.be'], true);
}

/** Converte i link YouTube in URL incorporabili; gli altri materiali non sono video. */
function youtubeEmbedUrl(?string $url): ?string {
    $parts = parse_url(trim((string) $url));
    if ($parts === false || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
        return null;
    }

    $host = strtolower($parts['host'] ?? '');
    $path = $parts['path'] ?? '';
    $videoId = null;
    if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
        $videoId = ltrim($path, '/');
    } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
        if ($path === '/watch') {
            parse_str($parts['query'] ?? '', $query);
            $videoId = $query['v'] ?? null;
        } elseif (preg_match('~^/(?:embed|shorts|live)/([^/]+)/?$~', $path, $matches)) {
            $videoId = $matches[1];
        }
    }

    if (!is_string($videoId) || !preg_match('/^[A-Za-z0-9_-]{11}$/D', $videoId)) {
        return null;
    }

    return 'https://www.youtube-nocookie.com/embed/' . $videoId;
}
