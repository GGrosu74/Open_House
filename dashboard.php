<?php
require_once 'config.php';
requireLogin();

$lang = $_GET['lang'] ?? 'it';
$langQuery = '?lang=' . urlencode($lang);

if ($_SESSION['user_type'] === 'istituto') {
    header('Location: dashboard_istituto.php' . $langQuery);
    exit;
} elseif ($_SESSION['user_type'] === 'partner') {
    header('Location: dashboard_partner.php' . $langQuery);
    exit;
} elseif ($_SESSION['user_type'] === 'admin') {
    header('Location: dashboard_admin.php' . $langQuery);
    exit;
} else {
    header('Location: dashboard_utente.php' . $langQuery);
    exit;
}
?>
