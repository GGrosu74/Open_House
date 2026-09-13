<?php if (PHP_SAPI !== 'cli') { http_response_code(404); exit; } ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Province e Regioni</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .test-container { max-width: 800px; margin: 40px auto; }
        .card { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .result-box { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; margin-top: 20px; }
        .api-test { background: #e7f3ff; border-left: 4px solid #007bff; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-gear"></i> Test Province e Regioni</h4>
            </div>
            <div class="card-body">

                <!-- Test 1: Dropdown Regioni -->
                <div class="mb-4">
                    <h5><i class="bi bi-map"></i> Test 1: Dropdown Regioni e Province</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="testRegione" class="form-label">Seleziona Regione</label>
                            <select class="form-select" id="testRegione" onchange="updateProvince()">
                                <option value="">-- Caricamento regioni --</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="testProvincia" class="form-label">Seleziona Provincia</label>
                            <select class="form-select" id="testProvincia">
                                <option value="">-- Seleziona prima una regione --</option>
                            </select>
                        </div>
                    </div>
                    <div class="result-box">
                        <p><strong>Regione selezionata:</strong> <span id="resultRegione">-</span></p>
                        <p><strong>Provincia selezionata:</strong> <span id="resultProvincia">-</span></p>
                    </div>
                </div>

                <!-- Test 2: API Test -->
                <div class="mb-4">
                    <h5><i class="bi bi-cloud-check"></i> Test 2: API Endpoints</h5>

                    <div class="api-test">
                        <button class="btn btn-sm btn-info" onclick="testAPIRegioni()">
                            <i class="bi bi-play-circle"></i> Test: GET /api_regioni_province.php?action=regioni
                        </button>
                        <div id="apiRegioni" class="mt-2"></div>
                    </div>

                    <div class="api-test">
                        <button class="btn btn-sm btn-info" onclick="testAPIProvince('Lombardia')">
                            <i class="bi bi-play-circle"></i> Test: GET /api_regioni_province.php?action=province&regione=Lombardia
                        </button>
                        <div id="apiProvince" class="mt-2"></div>
                    </div>

                    <div class="api-test">
                        <button class="btn btn-sm btn-info" onclick="testAPIAll()">
                            <i class="bi bi-play-circle"></i> Test: GET /api_regioni_province.php (Tutto)
                        </button>
                        <div id="apiAll" class="mt-2"></div>
                    </div>
                </div>

                <!-- Test 3: Dati Caricati -->
                <div class="mb-4">
                    <h5><i class="bi bi-list-check"></i> Test 3: Dati Caricati da province_regioni.js</h5>
                    <div class="result-box">
                        <p><strong>Totale Regioni:</strong> <span id="totalRegioni" class="badge bg-success">0</span></p>
                        <p><strong>Totale Province:</strong> <span id="totalProvince" class="badge bg-success">0</span></p>
                        <button class="btn btn-sm btn-secondary mt-2" onclick="testDatiLocali()">
                            <i class="bi bi-info-circle"></i> Mostra Dettagli
                        </button>
                        <div id="dettagliDati" class="mt-3"></div>
                    </div>
                </div>

                <!-- Test 4: Form Registrazione -->
                <div class="mb-4">
                    <h5><i class="bi bi-file-earmark-check"></i> Test 4: Form Registrazione</h5>
                    <p><a href="register.php" class="btn btn-primary" target="_blank">
                        <i class="bi bi-box-arrow-up-right"></i> Apri Form Registrazione
                    </a></p>
                </div>

            </div>
        </div>

        <!-- Log Console -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-terminal"></i> Console Log</h5>
            </div>
            <div class="card-body">
                <div id="console" style="background: #1e1e1e; color: #00ff00; font-family: monospace; padding: 15px; border-radius: 4px; min-height: 150px; max-height: 300px; overflow-y: auto; font-size: 0.85rem;">
                    [Ready for tests...]
                </div>
                <button class="btn btn-sm btn-secondary mt-2" onclick="clearConsole()">
                    <i class="bi bi-trash"></i> Pulisci Log
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="province_regioni.js"></script>

    <script>
        // Utility per logging
        function log(message, type = 'info') {
            const console = document.getElementById('console');
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? '#ff6b6b' : type === 'success' ? '#51cf66' : '#00ff00';
            const line = document.createElement('div');
            line.style.color = color;
            line.textContent = `[${timestamp}] ${message}`;
            console.appendChild(line);
            console.scrollTop = console.scrollHeight;
        }

        function clearConsole() {
            document.getElementById('console').innerHTML = '[Console pulita...]';
        }

        // Test 1: Aggiorna province basato su regione
        function updateProvince() {
            const regione = document.getElementById('testRegione').value;
            document.getElementById('resultRegione').textContent = regione || '-';

            populateProvince('testRegione', 'testProvincia');
            log(`Regione selezionata: ${regione}`, 'info');
        }

        document.getElementById('testProvincia').addEventListener('change', function() {
            document.getElementById('resultProvincia').textContent = this.value || '-';
            log(`Provincia selezionata: ${this.value}`, 'info');
        });

        // Test 2: API Tests
        function testAPIRegioni() {
            log('Fetching: GET /api_regioni_province.php?action=regioni', 'info');
            fetch('api_regioni_province.php?action=regioni')
                .then(r => r.json())
                .then(data => {
                    const result = document.getElementById('apiRegioni');
                    if (data.success) {
                        result.innerHTML = `<span class="success">✓ Success:</span> ${data.regioni.length} regioni caricate<br><small>${data.regioni.join(', ')}</small>`;
                        log(`API Regioni: ${data.regioni.length} regioni OK`, 'success');
                    } else {
                        result.innerHTML = `<span class="error">✗ Errore:</span> ${data.error}`;
                        log(`API Regioni: ERRORE - ${data.error}`, 'error');
                    }
                })
                .catch(e => {
                    document.getElementById('apiRegioni').innerHTML = `<span class="error">✗ Errore di fetch:</span> ${e.message}`;
                    log(`API Error: ${e.message}`, 'error');
                });
        }

        function testAPIProvince(regione) {
            log(`Fetching: GET /api_regioni_province.php?action=province&regione=${regione}`, 'info');
            fetch(`api_regioni_province.php?action=province&regione=${regione}`)
                .then(r => r.json())
                .then(data => {
                    const result = document.getElementById('apiProvince');
                    if (data.success) {
                        result.innerHTML = `<span class="success">✓ Success:</span> ${data.province.length} province in ${data.regione}<br><small>${data.province.join(', ')}</small>`;
                        log(`API Province: ${data.province.length} province per ${regione} OK`, 'success');
                    } else {
                        result.innerHTML = `<span class="error">✗ Errore:</span> ${data.error}`;
                        log(`API Province: ERRORE - ${data.error}`, 'error');
                    }
                })
                .catch(e => {
                    document.getElementById('apiProvince').innerHTML = `<span class="error">✗ Errore di fetch:</span> ${e.message}`;
                    log(`API Error: ${e.message}`, 'error');
                });
        }

        function testAPIAll() {
            log('Fetching: GET /api_regioni_province.php', 'info');
            fetch('api_regioni_province.php')
                .then(r => r.json())
                .then(data => {
                    const result = document.getElementById('apiAll');
                    if (data.success) {
                        result.innerHTML = `<span class="success">✓ Success:</span> ${data.count_regioni} regioni, ${data.count_province_total} province totali<br><small>API Status: Full dataset loaded</small>`;
                        log(`API All: ${data.count_regioni} regioni + ${data.count_province_total} province OK`, 'success');
                    } else {
                        result.innerHTML = `<span class="error">✗ Errore:</span> ${data.error}`;
                        log(`API All: ERRORE - ${data.error}`, 'error');
                    }
                })
                .catch(e => {
                    document.getElementById('apiAll').innerHTML = `<span class="error">✗ Errore di fetch:</span> ${e.message}`;
                    log(`API Error: ${e.message}`, 'error');
                });
        }

        // Test 3: Dati locali
        function testDatiLocali() {
            log('Analizzando dati da province_regioni.js...', 'info');

            const totalRegioni = REGIONI_ARRAY.length;
            const totalProvince = Object.values(REGIONI_PROVINCE).reduce((a, b) => a + b.length, 0);

            document.getElementById('totalRegioni').textContent = totalRegioni;
            document.getElementById('totalProvince').textContent = totalProvince;

            let html = '<table class="table table-sm"><thead><tr><th>Regione</th><th>Province</th></tr></thead><tbody>';
            Object.entries(REGIONI_PROVINCE).forEach(([regione, province]) => {
                html += `<tr><td>${regione}</td><td>${province.length}</td></tr>`;
            });
            html += '</tbody></table>';

            document.getElementById('dettagliDati').innerHTML = html;
            log(`Dati locali: ${totalRegioni} regioni, ${totalProvince} province totali`, 'success');
        }

        // Inizializzazione
        document.addEventListener('DOMContentLoaded', function() {
            log('Page loaded - Initializing tests...', 'info');

            // Popola i dropdown di test
            populateRegioni('testRegione');
            log('Test dropdown regioni inizializzato', 'success');

            // Mostra statistiche
            testDatiLocali();
        });
    </script>
</body>
</html>
