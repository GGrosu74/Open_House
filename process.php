<?php if (PHP_SAPI !== 'cli') { http_response_code(404); exit; } ?>
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Accesso diretto non consentito. Compila la form dalla pagina principale.";
    exit;
}

// Ricevi i dati dalla form
$fname = $_POST['fname'] ?? '';
$lname = $_POST['lname'] ?? '';
$sex = $_POST['sex'] ?? '';
$bloodType = $_POST['bloodType'] ?? '';
$birth = $_POST['birth'] ?? '';
$day = $_POST['day'] ?? '';
$location = $_POST['location'] ?? '';

// Verifica se tutti i campi sono compilati
if (empty($fname) || empty($lname) || empty($sex) || empty($bloodType) || empty($birth) || empty($day) || empty($location)) {
    echo "Errore: Tutti i campi sono obbligatori.";
    exit;
}

// Calcola età
$age = calcAge($birth);

// Verifica età
if ($age < 18 || $age > 110) {
    echo "Errore: Età non consentita.";
    exit;
}

// Calcola orario
$time = "8.30";
if ($day != 7) {
    if (($day % 2) == 1) {
        $time = "10.30";
    }
} else {
    $time = "11.30";
}

// Calcola cc
$cc = 150;
if ($age < 25) {
    $cc = 150;
} elseif ($age < 35) {
    $cc = 250;
} else {
    $cc = 200;
}

if ($sex == "female") {
    $cc /= 2;
}

// Aggiungi timestamp
$timestamp = date('Y-m-d H:i:s');

// Prepara dati per CSV
$data = [$timestamp, $fname, $lname, $sex, $bloodType, $birth, $day, $location, $time, $cc];

// Salva su CSV
$filename = '../data/prenotazioni.csv';
$dir = dirname($filename);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$file_exists = file_exists($filename);
$file = fopen($filename, 'a');
if ($file) {
    if (!$file_exists) {
        // Aggiungi header se file nuovo
        fputcsv($file, ['Timestamp', 'Nome', 'Cognome', 'Sesso', 'Gruppo Sanguigno', 'Data Nascita', 'Giorno', 'Residenza', 'Orario', 'CC'], ',', '"', '\\');
    }
    fputcsv($file, $data, ',', '"', '\\');
    fclose($file);
} else {
    echo "Errore nel salvare i dati.";
    exit;
}

// Conta prenotazioni totali
$lines = file($filename);
$total_prenotazioni = count($lines) - 1; // meno header

// Riassumi i dati salvati (mostra l'ultimo salvato e totale)
$content = "<h2>Riepilogo Prenotazione</h2>";
$content .= "<p>Timestamp: $timestamp</p>";
$content .= "<p>Nome: $fname $lname</p>";
$content .= "<p>Sesso: $sex</p>";
$content .= "<p>Gruppo Sanguigno: $bloodType</p>";
$content .= "<p>Data di Nascita: $birth</p>";
$content .= "<p>Giorno: $day</p>";
$content .= "<p>Residenza: $location</p>";
$content .= "<p>Orario: $time</p>";
$content .= "<p>CC: $cc</p>";
$content .= "<p>Totale prenotazioni salvate: $total_prenotazioni</p>";
$content .= '<br><a href="index.html">Torna alla pagina principale</a>';

// Funzione per calcolare età
function calcAge($birthday) {
    $birthday = new DateTime($birthday);
    $today = new DateTime();
    $years = $today->diff($birthday)->y;
    return $years;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riepilogo Prenotazione</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h1 class="text-primary text-center mb-4">Riepilogo Prenotazione</h1>
        <div class="card p-4 shadow">
            <?php echo $content; ?>
        </div>
        <footer class="bg-primary text-white text-center mt-4 py-3 rounded">
            <p>&copy; 2025 MyAmbulatorio. Tutti i diritti riservati.</p>
        </footer>
    </div>
</body>
</html>
