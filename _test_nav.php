<?php if (PHP_SAPI !== 'cli') { http_response_code(404); exit; } ?>
<?php
$_GET['lang'] = 'it';
ob_start();
include __DIR__ . '/index.php';
$html = ob_get_clean();

preg_match('/<nav[^>]*class="([^"]*)"/', $html, $navClass);
echo "Nav classes: " . ($navClass[1] ?? 'NOT FOUND') . "\n";

$css = file_get_contents(__DIR__ . '/style.css');
preg_match('/\.navbar\.bg-primary[\s\S]{0,200}/', $css, $rule);
echo "CSS rule: " . ($rule[0] ?? 'NOT FOUND') . "\n";

preg_match_all('/href="([^"]*style\.css[^"]*)"/', $html, $links);
echo "Style links: " . implode(', ', $links[1] ?? []) . "\n";
