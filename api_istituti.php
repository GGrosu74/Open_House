<?php
/**
 * API per ottenere la lista degli enti/partner
 * Restituisce JSON per chiamate AJAX
 */
require_once 'config.php';

header('Content-Type: application/json');

$regione = $_GET['regione'] ?? '';
$provincia = $_GET['provincia'] ?? '';
$tipologia_ente = $_GET['tipologia_ente'] ?? ($_GET['tipo_scuola'] ?? '');
$search = $_GET['search'] ?? '';

// Costruisci query
$query = "SELECT i.ID_Ente, i.Ragione_Sociale, i.Tipologia, i.Regione, i.Provincia, i.Comune, i.ID_Ente as id, i.Ragione_Sociale as nome, i.Tipologia as tipo_scuola, i.Cod_Mecc as codice_istituto,
                 COUNT(DISTINCT a.ID_Attivita) as totale_attivita, COUNT(DISTINCT p.id) as totale_prenotazioni
          FROM istituti_e_partner i
          LEFT JOIN attivita_eventi a ON i.ID_Ente = a.FK_Ente_Organizzatore AND a.Stato = 'pubblicata'
          LEFT JOIN prenotazioni p ON a.ID_Attivita = p.attivita_id AND p.stato = 'confermata'
          WHERE i.Stato_Validazione=1";

$params = [];

if (!empty($regione)) {
    $query .= " AND i.regione = ?";
    $params[] = $regione;
}

if (!empty($provincia)) {
    $query .= " AND i.provincia = ?";
    $params[] = $provincia;
}

if (!empty($tipologia_ente)) {
    $query .= " AND i.Tipologia = ?";
    $params[] = $tipologia_ente;
}

if (!empty($search)) {
    $query .= " AND (i.Ragione_Sociale LIKE ? OR i.indirizzo LIKE ? OR i.comune LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$page=max(1,(int)($_GET['page']??1));
$perPage=max(1,min(100,(int)($_GET['per_page']??25)));
$offset=($page-1)*$perPage;
$query .= " GROUP BY i.ID_Ente ORDER BY i.Ragione_Sociale ASC LIMIT $perPage OFFSET $offset";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $istituti = $stmt->fetchAll();

    // Ottieni regioni e province uniche per i filtri
    $stmt = $pdo->query("SELECT DISTINCT regione FROM istituti_e_partner WHERE regione IS NOT NULL AND regione != '' ORDER BY regione");
    $regioni = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->query("SELECT DISTINCT provincia FROM istituti_e_partner WHERE provincia IS NOT NULL AND provincia != '' ORDER BY provincia");
    $province = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'istituti' => $istituti,
        'page'=>$page,
        'per_page'=>$perPage,
        'regioni' => $regioni,
        'province' => $province
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Errore nel recupero dati',
        'istituti' => [],
        'regioni' => [],
        'province' => []
    ]);
}
?>
