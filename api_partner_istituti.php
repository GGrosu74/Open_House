<?php
/**
 * API per filtro istituti partner VR e FSL
 * Endpoint: api_partner_istituti.php
 *
 * Restituisce lista di istituti filtrati per tipo (partner_vr, partner_fsl)
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $partner_type = $_GET['partner_type'] ?? $_POST['partner_type'] ?? '';
    $regione = $_GET['regione'] ?? '';
    $provincia = $_GET['provincia'] ?? '';
    $search = $_GET['search'] ?? '';

    // Support optional filtering and pagination
    $stato = null; // 0,1,2 or null
    $per_page=max(1,min(100,(int)($_GET['per_page']??25))); // 0 = no limit
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

    $baseSelect = "FROM istituti_e_partner i";

    $whereClauses = ["1=1"];
    $params = [];

    // Default behavior: only approved (1) for backward compatibility
    if ($stato === null) {
        $whereClauses[] = 'i.Stato_Validazione = 1';
    } else {
        $whereClauses[] = 'i.Stato_Validazione = ?';
        $params[] = $stato;
    }

    // Filtra per tipo partner
    if ($partner_type === 'partner_vr') {
        // Partner VR: Aziende e strutture specializzate in realtà virtuale
        $whereClauses[] = "i.Tipologia IN ('ARENA_VR', 'ARENA_MOBILE', 'PARTNER_VR')";
    } elseif ($partner_type === 'partner_fsl') {
        // Partner FSL: aziende e partner non classificati come strutture VR.
        $whereClauses[] = "(i.Tipologia LIKE '%AZIENDA%'
                            OR (i.Tipologia LIKE '%PARTNER%' AND i.Tipologia <> 'PARTNER_VR'))";
    } elseif ($partner_type === 'istituti') {
        // Istituti scolastici
        $whereClauses[] = "i.Tipologia IN ('SCUOLA PRIMARIA', 'SCUOLA INFANZIA', 'SCUOLA PRIMO GRADO',
                                         'ISTITUTO COMPRENSIVO', 'LICEO CLASSICO', 'LICEO SCIENTIFICO',
                                         'ISTITUTO TECNICO', 'ISTITUTO PROFESSIONALE', 'ISTITUTO MAGISTRALE')";
    } elseif ($partner_type === '' || $partner_type === 'tutti') {
        $whereClauses[] = "(i.Tipologia IN ('ARENA_VR', 'ARENA_MOBILE', 'PARTNER_VR')
                            OR i.Tipologia LIKE '%AZIENDA%'
                            OR (i.Tipologia LIKE '%PARTNER%' AND i.Tipologia <> 'PARTNER_VR'))";
    }

    // Filtra per regione
    if (!empty($regione)) {
        $whereClauses[] = 'i.Regione = ?';
        $params[] = $regione;
    }

    // Filtra per provincia
    if (!empty($provincia)) {
        $whereClauses[] = 'i.Provincia = ?';
        $params[] = $provincia;
    }

    // Filtra per ricerca nel nome
    if (!empty($search)) {
        $whereClauses[] = '(i.Ragione_Sociale LIKE ? OR i.Indirizzo LIKE ? OR i.Comune LIKE ?)';
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    $where = ' WHERE ' . implode(' AND ', $whereClauses);

    // Count totale per paginazione
    $countQuery = "SELECT COUNT(*) as total " . $baseSelect . $where;
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalMatching = (int)$countStmt->fetchColumn();

    // Costruisci query dati con paginazione
    $query = "SELECT i.ID_Ente, i.Ragione_Sociale, i.Tipologia, i.Email, i.Indirizzo,
                     i.Comune, i.Provincia, i.Regione, i.CF_PIVA, i.Coordinate_GPS,
                     i.Stato_Validazione
              " . $baseSelect . $where . " ORDER BY i.Ragione_Sociale ASC";

    $execParams = $params;
    if ($per_page > 0) {
        $offset = ($page - 1) * $per_page;
        $query .= " LIMIT ?, ?";
        $execParams[] = $offset;
        $execParams[] = $per_page;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($execParams);
    $istituti = $stmt->fetchAll();

    // Restituisci risultati
    echo json_encode([
        'success' => true,
        'partner_type' => $partner_type,
        'count' => count($istituti),
        'total_matching' => $totalMatching,
        'page' => $page,
        'per_page' => $per_page,
        'data' => $istituti
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'=>'Errore nel recupero dei risultati'
    ]);
}
