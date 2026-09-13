<?php if (realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__) { http_response_code(404); exit; } ?>
<?php
if (!isset($lang)) {
    $lang = $_GET['lang'] ?? 'it';
}
if (!isset($active_page)) {
    $active_page = '';
}
include __DIR__ . '/navbar.php';
