<?php
/**
 * Pagina di visualizzazione degli istituti partner
 * Mostra liste filtrabili di partner VR e partner FSL
 */

require_once 'config.php';

$lang = $_GET['lang'] ?? 'it';

// Determina il tipo di visualizzazione
$view_type = trim($_GET['view'] ?? 'tutti');  // partner_vr, partner_fsl, istituti, tutti
$search = trim($_GET['search'] ?? '');
$regione = trim($_GET['regione'] ?? '');

$allowed_views = ['partner_vr', 'partner_fsl', 'istituti', 'tutti'];
if (!in_array($view_type, $allowed_views, true)) {
    $view_type = '';
}

// Debug: Log dei parametri ricevuti
// error_log("view_type: $view_type, search: $search, regione: $regione");

// Funzione per ottenere gli istituti
$istituti = [];
if ($view_type === '') {
    $view_type = 'tutti';
}

if ($view_type === 'partner_vr') {
    $page_title = $lang === 'it' ? 'Partner VR' : 'VR Partners';
} elseif ($view_type === 'partner_fsl') {
    $page_title = $lang === 'it' ? 'Partner FSL' : 'FSL Partners';
} elseif ($view_type === 'istituti') {
    $page_title = $lang === 'it' ? 'Istituti' : 'Institutions';
} else {
    $page_title = $lang === 'it' ? 'Partner VR e FSL' : 'VR and FSL Partners';
}

// default: show all (including non-validati) but indicate validation status
// if user selected a specific view, filter accordingly

if ($view_type === '') {
    $info = 'Seleziona una categoria per vedere i risultati.';
}

try {
    $hasTelefono = tableHasColumn($pdo, 'istituti_e_partner', 'Telefono');
    $telefonoSelect = $hasTelefono ? 'i.Telefono' : 'NULL AS Telefono';

    $query = "SELECT i.ID_Ente, i.Ragione_Sociale, i.Tipologia, i.Email, {$telefonoSelect},
                     i.Indirizzo, i.Comune, i.Provincia, i.Regione, i.CF_PIVA,
                     i.Coordinate_GPS, i.Stato_Validazione
              FROM istituti_e_partner i
              WHERE i.Stato_Validazione=1";

    $params = [];

    // Filtra per tipo visualizzazione
    if ($view_type === 'partner_vr') {
        $query .= " AND i.Tipologia IN ('ARENA_VR', 'ARENA_MOBILE', 'PARTNER_VR')";
    } elseif ($view_type === 'partner_fsl') {
        $query .= " AND (i.Tipologia LIKE '%AZIENDA%'
                          OR (i.Tipologia LIKE '%PARTNER%' AND i.Tipologia <> 'PARTNER_VR'))";
    } elseif ($view_type === 'istituti') {
        $query .= " AND i.Tipologia IN ('SCUOLA PRIMARIA', 'SCUOLA INFANZIA', 'SCUOLA PRIMO GRADO',
                                         'ISTITUTO COMPRENSIVO', 'LICEO CLASSICO', 'LICEO SCIENTIFICO',
                                         'ISTITUTO TECNICO', 'ISTITUTO PROFESSIONALE', 'ISTITUTO MAGISTRALE')";
    } else {
        $query .= " AND (i.Tipologia IN ('ARENA_VR', 'ARENA_MOBILE', 'PARTNER_VR')
                          OR i.Tipologia LIKE '%AZIENDA%'
                          OR (i.Tipologia LIKE '%PARTNER%' AND i.Tipologia <> 'PARTNER_VR'))";
    }

    // Filtra per regione
    if (!empty($regione)) {
        $query .= " AND i.Regione = ?";
        $params[] = $regione;
    }

    // Filtra per ricerca
    if (!empty($search)) {
        $query .= " AND (i.Ragione_Sociale LIKE ? OR i.Comune LIKE ? OR i.Provincia LIKE ? OR i.Regione LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    $query .= " ORDER BY i.Regione, i.Provincia, i.Ragione_Sociale";

    $page=max(1,(int)($_GET['page']??1)); $pageSize=25; $offset=($page-1)*$pageSize;
$query.=' LIMIT '.($pageSize+1).' OFFSET '.$offset;
$stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $istituti = $stmt->fetchAll(PDO::FETCH_ASSOC);
$hasMore=count($istituti)>$pageSize; if($hasMore) array_pop($istituti);

} catch (Exception $e) {
    $error = "Errore nel caricamento: ";
}

// Ottieni lista regioni
$regioni = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT Regione FROM istituti_e_partner WHERE Regione IS NOT NULL ORDER BY Regione");
    $regioni = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Log error silently
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(135deg, #003d82 0%, #0066cc 50%, #0082e6 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        main {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            margin: 30px auto;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 1400px;
        }

        .header-section {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 3px solid #0066cc;
        }

        .header-section img {
            width: auto;
            max-width: 100%;
            max-height: 180px;
            object-fit: contain;
            margin: 0 auto 15px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }

        .header-section h1 {
            color: #0066cc;
            font-weight: 700;
            margin: 0;
        }

        .view-selector {
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #f0f4f9 0%, #e8eef5 100%);
            border: 2px solid #0066cc;
            border-radius: 12px;
        }

        .view-selector label {
            font-weight: 600;
            margin-bottom: 10px;
            color: #003d82;
            display: block;
        }

        .view-selector select {
            padding: 12px 16px;
            background: white;
            border: 2px solid #0066cc;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            color: #003d82;
            width: 100%;
            max-width: 400px;
        }

        .view-selector select:hover,
        .view-selector select:focus {
            outline: none;
            border-color: #003d82;
            box-shadow: 0 0 8px rgba(0, 102, 204, 0.3);
            background-color: #f0f4f9;
        }

        .filter-section {
            background: linear-gradient(135deg, #f0f4f9 0%, #e8eef5 100%);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 2px solid #0066cc;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #003d82;
        }

        .filter-group input,
        .filter-group select {
            padding: 12px;
            border: 2px solid #0066cc;
            border-radius: 8px;
            background: white;
            transition: all 0.3s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #003d82;
            box-shadow: 0 0 8px rgba(0, 102, 204, 0.3);
        }

        .btn-filter {
            padding: 12px 24px;
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-filter:hover {
            background: linear-gradient(135deg, #0052a3 0%, #003d82 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
        }

        .btn-reset {
            padding: 12px 24px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-reset:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .istituti-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .istituto-card {
            background: white;
            border: 2px solid #e8eef5;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 102, 204, 0.1);
            transition: all 0.3s;
        }

        .istituto-card:hover {
            box-shadow: 0 8px 25px rgba(0, 102, 204, 0.2);
            transform: translateY(-4px);
            border-color: #0066cc;
        }

        .istituto-name {
            font-weight: 700;
            margin-bottom: 12px;
            color: #003d82;
        }

        .istituto-type {
            display: inline-block;
            padding: 6px 14px;
            background: linear-gradient(135deg, #e8f0ff 0%, #d4e3ff 100%);
            color: #0052a3;
            border: 1px solid #0066cc;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .istituto-info {
            font-size: 0.95rem;
            line-height: 1.6;
            color: #666;
        }

        .istituto-info p {
            margin: 8px 0;
        }

        .info-label {
            font-weight: 600;
            color: #333;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #666;
        }

        .results-count {
            margin-bottom: 20px;
            font-size: 1.1rem;
            font-weight: 600;
            color: #003d82;
        }

        .btn-detail {
            margin-top: 15px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-detail:hover {
            background: linear-gradient(135deg, #0052a3 0%, #003d82 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
        }
    </style>
</head>
<body class="partner-page">
    <?php include 'navbar.php'; ?>

    <main class="container">
        <div class="header-section">
            <img src="image/Logo_ partner.png" alt="Logo Partner">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
        </div>

        <!-- Filtri -->
        <form method="GET" class="filter-section">
            <input type="hidden" name="lang" value="<?php echo htmlspecialchars($lang); ?>">
            <!-- Selezione tipo visualizzazione -->
            <div class="view-selector">
                <label for="viewType">Seleziona cosa visualizzare:</label>
                <select id="viewType" name="view" onchange="this.form.submit()">
                    <option value="">Seleziona</option>
                        <option value="partner_vr" <?php echo ($view_type === 'partner_vr') ? 'selected' : ''; ?>>🥽 Partner VR</option>
                        <option value="partner_fsl" <?php echo ($view_type === 'partner_fsl') ? 'selected' : ''; ?>>📚 Partner FSL</option>
                        <option value="istituti" <?php echo ($view_type === 'istituti') ? 'selected' : ''; ?>>🏫 Istituti</option>
                </select>
            </div>

            <div class="filter-row">
                <div class="filter-group">
                    <label for="search">Ricerca Nome</label>
                    <input type="text" id="search" name="search" placeholder="Cerca per nome..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div class="filter-group">
                    <label for="regione">Regione</label>
                    <select id="regione" name="regione">
                        <option value="">Tutte le regioni</option>
                        <?php foreach ($regioni as $reg): ?>
                            <option value="<?php echo htmlspecialchars($reg); ?>"
                                    <?php echo ($regione === $reg) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($reg); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <button type="submit" class="btn-filter">🔍 Filtra</button>
                </div>

                <div class="filter-group">
                    <a href="?view=<?php echo htmlspecialchars($view_type); ?>&lang=<?php echo htmlspecialchars($lang); ?>" class="btn-reset">Azzera Filtri</a>
                </div>
            </div>
        </form>

        <!-- Risultati -->
        <?php if (isset($info)): ?>
            <div class="partner-alert-info">
                <?php echo htmlspecialchars($info); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="partner-alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($view_type !== ''): ?>
            <div class="results-count">
                ✓ <?php echo count($istituti); ?> risultati trovati
            </div>

            <?php if (empty($istituti)): ?>
                <div class="no-results">
                    <h3>Nessun risultato trovato</h3>
                    <p>Prova a modificare i filtri di ricerca</p>
                </div>
            <?php else: ?>
                <div class="istituti-grid">
                    <?php foreach ($istituti as $istituto): ?>
                        <div class="istituto-card">
                            <div class="istituto-name"><?php echo htmlspecialchars($istituto['Ragione_Sociale']); ?></div>

                        <div class="istituto-type">
                            <?php
                            $tipologie_map = [
                                'AZIENDA' => '🏢',
                                'ARENA_VR' => '🥽',
                                'PARTNER_VR' => '🥽',
                                'SCUOLA PRIMARIA' => '🏫',
                                'SCUOLA INFANZIA' => '👶',
                                'ISTITUTO TECNICO' => '⚙️',
                                'LICEO' => '📚',
                            ];
                            $icon = '📍';
                            foreach ($tipologie_map as $key => $value) {
                                if (strpos($istituto['Tipologia'], $key) !== false) {
                                    $icon = $value;
                                    break;
                                }
                            }
                            echo $icon . ' ' . htmlspecialchars($istituto['Tipologia']);
                            ?>
                        </div>

                        <div class="istituto-info">
                            <?php if (!empty($istituto['Provincia'])): ?>
                                <p><span class="info-label">Provincia:</span> <?php echo htmlspecialchars($istituto['Provincia']); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($istituto['Regione'])): ?>
                                <p><span class="info-label">Regione:</span> <?php echo htmlspecialchars($istituto['Regione']); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($istituto['Email'])): ?>
                                <p><span class="info-label">Email:</span> <a href="mailto:<?php echo htmlspecialchars($istituto['Email']); ?>">
                                    <?php echo htmlspecialchars($istituto['Email']); ?></a></p>
                            <?php endif; ?>

                            <?php if (!empty($istituto['Telefono'])): ?>
                                <p><span class="info-label">Tel:</span> <?php echo htmlspecialchars($istituto['Telefono']); ?></p>
                            <?php endif; ?>
                        </div>

                        <a href="istituto_dettaglio.php?id=<?php echo $istituto['ID_Ente']; ?>&lang=<?php echo htmlspecialchars($lang); ?>" class="btn-detail">
                            Visualizza Dettagli →
                        </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inizializza il dropdown del view_type con il valore corretto
        document.addEventListener('DOMContentLoaded', function() {
            const viewSelect = document.getElementById('viewType');
            const currentView = '<?php echo $view_type; ?>';
            if (viewSelect && currentView) {
                viewSelect.value = currentView;
            }
        });
    </script>
<?= paginationLinks($page??1,$hasMore??false) ?>
</body>
</html>
