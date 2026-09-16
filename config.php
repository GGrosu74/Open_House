<?php
// Supply OPENHOUSE_* environment variables on the server; never commit secrets.
define('DB_HOST', getenv('OPENHOUSE_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('OPENHOUSE_DB_PORT') ?: '3306');
define('DB_NAME', getenv('OPENHOUSE_DB_NAME') ?: 'open_house');
define('DB_USER', getenv('OPENHOUSE_DB_USER') ?: 'root');
define('DB_PASS', getenv('OPENHOUSE_DB_PASS') ?: '');
define('BASE_URL', rtrim(getenv('OPENHOUSE_BASE_URL') ?: 'http://localhost/Open_House', '/'));
define('DEBUG_MODE', getenv('OPENHOUSE_DEBUG') === '1');
define('SESSION_LIFETIME', 43200);
date_default_timezone_set('Europe/Rome');
ini_set('display_errors','0'); ini_set('log_errors','1');
set_exception_handler(function(Throwable $error) {
    error_log((string)$error);
    if (PHP_SAPI === 'cli') { fwrite(STDERR, "Operazione fallita. Consultare il log.\n"); exit(1); }
    http_response_code(500); echo 'Errore di sistema. Riprova più tardi.';
});
$pdo=new PDO("mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4",DB_USER,DB_PASS,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
if(PHP_SAPI !== 'cli' && session_status()===PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode','1'); ini_set('session.gc_maxlifetime',(string)SESSION_LIFETIME);
    session_set_cookie_params(['lifetime'=>SESSION_LIFETIME,'path'=>'/',
        'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
require_once __DIR__.'/app_security.php';
// Funzioni di utilità
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['user_type'], $roles, true)) {
        header('Location: dashboard.php');
        exit;
    }
}

function requireIstituto() {
    requireRole(['istituto']);
}

function requireUtente() {
    requireRole(['utente']);
}

function requirePartner() {
    requireRole(['partner']);
}

function requireAdmin() {
    requireRole(['admin']);
    if (!empty($_SESSION['must_change_password']) && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'admin_password.php') {
        header('Location: admin_password.php');
        exit;
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function sendHtmlEmail($to, $subject, $htmlBody) {
    if (!filter_var($to,FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/',$to.$subject)) return false;
    $transport=getenv('OPENHOUSE_MAIL_TRANSPORT') ?: 'disabled';
    if($transport==='log' && getenv('OPENHOUSE_MAIL_LOG')) {
        return file_put_contents(getenv('OPENHOUSE_MAIL_LOG'),json_encode(['to'=>$to,'subject'=>$subject,'body'=>$htmlBody],JSON_UNESCAPED_UNICODE)."\n",FILE_APPEND|LOCK_EX)!==false;
    }
    if($transport!=='mail') return false;
    $from=getenv('OPENHOUSE_MAIL_FROM');
    if(!$from || !filter_var($from,FILTER_VALIDATE_EMAIL)) return false;
    return mail($to,$subject,$htmlBody,['MIME-Version'=>'1.0','Content-type'=>'text/html; charset=UTF-8','From'=>$from]);
}

function tableHasColumn(PDO $pdo, string $tableName, string $columnName): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$tableName, $columnName]);
    return (int) $stmt->fetchColumn() > 0;
}

function getTableColumns(PDO $pdo, string $table): array {
    $stmt = $pdo->query('SHOW COLUMNS FROM ' . $table);
    $columns = [];
    foreach ($stmt->fetchAll() as $row) {
        $columns[] = $row['Field'];
    }
    return $columns;
}

function getUserTable(PDO $pdo, bool $required = true): ?string {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('utenti', $tables, true)) {
        $cached = 'utenti';
        return $cached;
    }
    if (in_array('utenti_finali', $tables, true)) {
        $cached = 'utenti_finali';
        return $cached;
    }
    if ($required) {
        throw new RuntimeException('Nessuna tabella utenti disponibile (utenti / utenti_finali).');
    }
    return null;
}

function insertIstitutoPartner(PDO $pdo, array $data): void {
    $available = getTableColumns($pdo, 'istituti_e_partner');
    $map = [
        'Ragione_Sociale' => $data['Ragione_Sociale'] ?? null,
        'Tipologia' => $data['Tipologia'] ?? null,
        'CF_PIVA' => $data['CF_PIVA'] ?? null,
        'Cod_Mecc' => $data['Cod_Mecc'] ?? null,
        'Cod_REA' => $data['Cod_REA'] ?? null,
        'Indirizzo' => $data['Indirizzo'] ?? null,
        'Comune' => $data['Comune'] ?? null,
        'Provincia' => $data['Provincia'] ?? null,
        'Regione' => $data['Regione'] ?? null,
        'Coordinate_GPS' => $data['Coordinate_GPS'] ?? null,
        'Email' => $data['Email'] ?? null,
        'Telefono' => $data['Telefono'] ?? null,
        'descrizione' => $data['descrizione'] ?? null,
        'password' => $data['password'] ?? null,
        'Stato_Validazione' => 0,
    ];

    $fields = [];
    $values = [];
    $params = [];
    foreach ($map as $field => $value) {
        if (in_array($field, $available, true)) {
            $fields[] = $field;
            $values[] = '?';
            $params[] = $value;
        }
    }

    if (empty($fields)) {
        throw new RuntimeException('Tabella istituti_e_partner non compatibile con la registrazione.');
    }

    $sql = 'INSERT INTO istituti_e_partner (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

/** Normalizza riga attivita_eventi per i form legacy (chiavi minuscole). */
function normalizeAttivitaRow(array $row): array {
    return [
        'titolo' => $row['Titolo'] ?? $row['titolo'] ?? '',
        'descrizione' => $row['Descrizione'] ?? $row['descrizione'] ?? '',
        'tipo_attivita' => $row['Tipo_Attivita'] ?? $row['tipo_attivita'] ?? 'presentazione',
        'data_ora' => $row['Data_Ora'] ?? $row['data_ora'] ?? '',
        'durata_minuti' => $row['Durata_Minuti'] ?? $row['durata_minuti'] ?? 60,
        'max_partecipanti' => $row['Max_Posti'] ?? $row['max_partecipanti'] ?? 50,
        'supporta_vr' => $row['Supporta_VR'] ?? $row['supporta_vr'] ?? 0,
        'url_vr' => $row['Link_WebXR'] ?? $row['url_vr'] ?? '',
        'materiali_url' => $row['Materiali_URL'] ?? $row['materiali_url'] ?? '',
        'stato' => $row['Stato'] ?? $row['stato'] ?? 'bozza',
    ];
}

/** Restituisce un collegamento WebXR valido anche per record legacy del braccio robotico. */
function resolveWebxrUrl(string $title, ?string $url): string {
    if (stripos($title, 'braccio robotico') !== false || stripos($title, 'insect robo') !== false) {
        return 'https://Novia-RDI-XR-Robotics.github.io/a-frame-xr-tutorial/';
    }

    return trim((string) $url);
}
initializeSecurity($pdo);
