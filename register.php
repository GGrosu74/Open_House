<?php
require_once 'config.php';

$error = '';
$success = '';
$showSuccessPopup = false;
$lang = $_GET['lang'] ?? 'it';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_type = $_POST['user_type'] ?? '';
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($user_type) || empty($email) || empty($password)) {
        $error = 'Compila tutti i campi obbligatori.';
    } elseif ($password !== $confirm_password) {
        $error = 'Le password non corrispondono.';
    } elseif (strlen($password) < 8) {
        $error = 'La password deve essere di almeno 8 caratteri.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email non valida.';
    } else {
        try {
            if ($user_type === 'utente') {
                $nome = sanitize($_POST['utente_nome'] ?? '');
                $cognome = sanitize($_POST['utente_cognome'] ?? '');
                $tipo_utente = $_POST['utente_tipo'] ?? '';
                $data_nascita = $_POST['utente_data_nascita'] ?? null;
                $telefono = sanitize($_POST['utente_telefono'] ?? '');

                if (empty($nome) || empty($cognome) || !in_array($tipo_utente,['studente','genitore','docente'],true)) {
                    throw new RuntimeException('Compila tutti i campi obbligatori per utente finale.');
                }

                $userTable = getUserTable($pdo);
                $stmt = $pdo->prepare("INSERT INTO {$userTable} (nome, cognome, email, password, tipo_utente, data_nascita, telefono) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $nome,
                    $cognome,
                    $email,
                    hashPassword($password),
                    $tipo_utente,
                    $data_nascita ?: null,
                    $telefono ?: null,
                ]);
                $userId = (int) $pdo->lastInsertId();

                sendHtmlEmail(
                    $email,
                    'Conferma registrazione Open House',
                    '<p>Ciao ' . htmlspecialchars($nome) . ',</p><p>la registrazione come ' . htmlspecialchars($tipo_utente) . ' e stata completata con successo.</p><p>Open House</p>'
                );

                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_type'] = 'utente';
                $_SESSION['user_name'] = trim($nome . ' ' . $cognome);
                $_SESSION['user_email'] = $email;
                $_SESSION['success'] = 'Registrazione completata. Hai effettuato automaticamente l’accesso.';
                header('Location: dashboard.php?lang=' . urlencode($lang));
                exit;
            } elseif ($user_type === 'istituto') {
                $ragioneSociale = sanitize($_POST['istituto_ragione_sociale'] ?? '');
                $tipologia = sanitize($_POST['istituto_tipologia'] ?? '');
                $cfPiva = sanitize($_POST['istituto_cf_piva'] ?? '');
                $codMecc = sanitize($_POST['istituto_cod_mecc'] ?? '');
                $indirizzo = sanitize($_POST['istituto_indirizzo'] ?? '');
                $comune = sanitize($_POST['istituto_comune'] ?? '');
                $provincia = sanitize($_POST['istituto_provincia'] ?? '');
                $regione = sanitize($_POST['istituto_regione'] ?? '');
                $coordinate = sanitize($_POST['istituto_coordinate_gps'] ?? '');
                $telefono = sanitize($_POST['istituto_telefono'] ?? '');
                $descrizione = sanitize($_POST['istituto_descrizione'] ?? '');

                if (empty($ragioneSociale) || !in_array($tipologia,['SCUOLA INFANZIA','SCUOLA PRIMARIA','SCUOLA PRIMO GRADO','SCUOLA SECONDARIA DI SECONDO GRADO','UNIVERSITA'],true) || empty($codMecc) || empty($provincia) || empty($regione)) {
                    throw new RuntimeException('Compila tutti i campi obbligatori per istituto.');
                }

                insertIstitutoPartner($pdo, [
                    'Ragione_Sociale' => $ragioneSociale,
                    'Tipologia' => $tipologia,
                    'CF_PIVA' => $cfPiva ?: null,
                    'Cod_Mecc' => $codMecc ?: null,
                    'Cod_REA' => null,
                    'Indirizzo' => $indirizzo ?: null,
                    'Comune' => $comune ?: null,
                    'Provincia' => $provincia,
                    'Regione' => $regione,
                    'Coordinate_GPS' => $coordinate ?: null,
                    'Email' => $email,
                    'Telefono' => $telefono ?: null,
                    'descrizione' => $descrizione ?: null,
                    'password' => hashPassword($password),
                ]);

                $success = 'Registrazione istituto ricevuta. Attendi la validazione.';
                sendHtmlEmail(
                    $email,
                    'Conferma registrazione istituto Open House',
                    '<p>Gentile ' . htmlspecialchars($ragioneSociale) . ',</p><p>la registrazione e stata ricevuta ed e in attesa di validazione.</p><p>Open House</p>'
                );
            } elseif ($user_type === 'partner') {
                $ragioneSociale = sanitize($_POST['partner_ragione_sociale'] ?? '');
                $tipoPartner = $_POST['partner_tipologia'] ?? '';
                $cfPiva = sanitize($_POST['partner_cf_piva'] ?? '');
                $codRea = sanitize($_POST['partner_cod_rea'] ?? '');
                $indirizzo = sanitize($_POST['partner_indirizzo'] ?? '');
                $comune = sanitize($_POST['partner_comune'] ?? '');
                $provincia = sanitize($_POST['partner_provincia'] ?? '');
                $regione = sanitize($_POST['partner_regione'] ?? '');
                $coordinate = sanitize($_POST['partner_coordinate_gps'] ?? '');
                $telefono = sanitize($_POST['partner_telefono'] ?? '');
                $descrizione = sanitize($_POST['partner_descrizione'] ?? '');

                $tipologiePartner = ['AZIENDA_FSL', 'PARTNER_VR'];
                if (empty($ragioneSociale) || !in_array($tipoPartner, $tipologiePartner, true) || empty($provincia) || empty($regione)) {
                    throw new RuntimeException('Seleziona la tipologia partner e compila tutti i campi obbligatori.');
                }
                if (empty($codRea)) {
                    throw new RuntimeException('Il Codice REA è obbligatorio per tutti i partner.');
                }

                insertIstitutoPartner($pdo, [
                    'Ragione_Sociale' => $ragioneSociale,
                    'Tipologia' => $tipoPartner,
                    'CF_PIVA' => $cfPiva ?: null,
                    'Cod_REA' => $codRea,
                    'Indirizzo' => $indirizzo ?: null,
                    'Comune' => $comune ?: null,
                    'Provincia' => $provincia,
                    'Regione' => $regione,
                    'Coordinate_GPS' => $coordinate ?: null,
                    'Email' => $email,
                    'Telefono' => $telefono ?: null,
                    'descrizione' => $descrizione ?: null,
                    'password' => hashPassword($password),
                ]);

                $nomeTipoPartner = $tipoPartner === 'PARTNER_VR' ? 'Partner VR' : 'Partner FSL';
                $success = 'Registrazione come ' . $nomeTipoPartner . ' ricevuta. Attendi la validazione.';
                sendHtmlEmail(
                    $email,
                    'Conferma registrazione partner Open House',
                    '<p>Gentile ' . htmlspecialchars($ragioneSociale) . ',</p><p>la registrazione come ' . htmlspecialchars($nomeTipoPartner) . ' è stata ricevuta ed è in attesa di validazione.</p><p>Open House</p>'
                );
            } else {
                throw new RuntimeException('Tipo utente non valido.');
            }

            $showSuccessPopup = true;
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                $error = 'Email gia registrata.';
            } else {
                $error = 'Errore durante la registrazione: ';
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

$translations = [
    'it' => [
        'title' => 'Registrazione - Open House',
        'register' => 'Registrati',
        'user_type' => 'Tipo utente',
        'istituto' => 'Istituto',
        'utente' => 'Utente',
        'partner' => 'Partner VR/FSL',
        'nome' => 'Nome',
        'cognome' => 'Cognome',
        'ragione_sociale' => 'Ragione sociale',
        'tipo_partner' => 'Tipologia partner',
        'citta' => 'Citta',
        'email' => 'Email',
        'password' => 'Password',
        'confirm_password' => 'Conferma Password',
        'tipo_scuola' => 'Tipo scuola',
        'tipo_utente' => 'Tipo utente',
        'indirizzo' => 'Indirizzo',
        'telefono' => 'Telefono',
        'descrizione' => 'Descrizione',
        'data_nascita' => 'Data di nascita',
        'has_account' => 'Hai già un account?',
        'login' => 'Accedi'
    ],
    'en' => [
        'title' => 'Register - Open House',
        'register' => 'Register',
        'user_type' => 'User type',
        'istituto' => 'Institution',
        'utente' => 'User',
        'partner' => 'VR/FSL Partner',
        'nome' => 'Name',
        'cognome' => 'Surname',
        'ragione_sociale' => 'Company name',
        'tipo_partner' => 'Partner type',
        'citta' => 'City',
        'email' => 'Email',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'tipo_scuola' => 'School type',
        'tipo_utente' => 'User type',
        'indirizzo' => 'Address',
        'telefono' => 'Phone',
        'descrizione' => 'Description',
        'data_nascita' => 'Date of birth',
        'has_account' => 'Already have an account?',
        'login' => 'Login'
    ]
];

$t = $translations[$lang];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php $active_page = 'register'; include 'header.php'; ?>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4"><?= $t['register'] ?></h2>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>

                        <form method="POST" id="registerForm">
<?= csrfField() ?>
                            <div class="mb-3">
                                <label for="user_type" class="form-label"><?= $t['user_type'] ?> *</label>
                                <select class="form-select" id="user_type" name="user_type" required onchange="toggleFields()">
                                    <option value="">-- Seleziona --</option>
                                    <option value="istituto"><?= $t['istituto'] ?></option>
                                    <option value="utente"><?= $t['utente'] ?></option>
                                    <option value="partner"><?= $t['partner'] ?></option>
                                </select>
                            </div>

                            <!-- Campi Istituto -->
                            <div id="istitutoFields" style="display: none;">
                                <div class="mb-3">
                                    <label for="istituto_ragione_sociale" class="form-label">Ragione sociale *</label>
                                    <input type="text" class="form-control" id="istituto_ragione_sociale" name="istituto_ragione_sociale" data-required-for="istituto">
                                </div>

                                <div class="mb-3">
                                    <label for="istituto_tipologia" class="form-label">Tipologia *</label>
                                    <select class="form-select" id="istituto_tipologia" name="istituto_tipologia" data-required-for="istituto">
                                        <option value="">-- Seleziona --</option>
                                        <option value="SCUOLA INFANZIA">Scuola dell'Infanzia</option>
                                        <option value="SCUOLA PRIMARIA">Scuola Primaria</option>
                                        <option value="SCUOLA PRIMO GRADO">Scuola Secondaria di Primo Grado</option>
                                        <option value="SCUOLA SECONDARIA DI SECONDO GRADO">Scuola Secondaria di Secondo Grado</option>
                                        <option value="UNIVERSITA">Universita</option>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="istituto_cf_piva" class="form-label">CF/PIVA</label>
                                        <input type="text" class="form-control" id="istituto_cf_piva" name="istituto_cf_piva">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="istituto_cod_mecc" class="form-label">Codice Meccanografico *</label>
                                        <input type="text" class="form-control" id="istituto_cod_mecc" name="istituto_cod_mecc" data-required-for="istituto">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="istituto_indirizzo" class="form-label"><?= $t['indirizzo'] ?></label>
                                    <textarea class="form-control" id="istituto_indirizzo" name="istituto_indirizzo" rows="2"></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="istituto_regione" class="form-label">Regione *</label>
                                        <select class="form-select" id="istituto_regione" name="istituto_regione" data-required-for="istituto" onchange="populateProvince('istituto_regione', 'istituto_provincia')">
                                            <option value="">-- Seleziona una Regione --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="istituto_provincia" class="form-label">Provincia *</label>
                                        <select class="form-select" id="istituto_provincia" name="istituto_provincia" data-required-for="istituto">
                                            <option value="">-- Seleziona una Provincia --</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="istituto_comune" class="form-label">Comune/Citta</label>
                                    <input type="text" class="form-control" id="istituto_comune" name="istituto_comune" placeholder="Es. Milano">
                                </div>

                                <div class="mb-3">
                                    <label for="istituto_coordinate_gps" class="form-label">Coordinate GPS</label>
                                    <input type="text" class="form-control" id="istituto_coordinate_gps" name="istituto_coordinate_gps" placeholder="Es. 45.4642, 9.1900">
                                </div>

                                <div class="mb-3">
                                    <label for="istituto_telefono" class="form-label"><?= $t['telefono'] ?></label>
                                    <input type="tel" class="form-control" id="istituto_telefono" name="istituto_telefono">
                                </div>

                                <div class="mb-3">
                                    <label for="istituto_descrizione" class="form-label"><?= $t['descrizione'] ?></label>
                                    <textarea class="form-control" id="istituto_descrizione" name="istituto_descrizione" rows="3"></textarea>
                                </div>
                            </div>

                            <!-- Campi Partner -->
                            <div id="partnerFields" style="display: none;">
                                <div class="mb-3">
                                    <label for="partner_ragione_sociale" class="form-label"><?= $t['ragione_sociale'] ?> *</label>
                                    <input type="text" class="form-control" id="partner_ragione_sociale" name="partner_ragione_sociale" data-required-for="partner">
                                </div>

                                <div class="mb-3">
                                    <label for="partner_tipologia" class="form-label"><?= $t['tipo_partner'] ?> *</label>
                                    <select class="form-select" id="partner_tipologia" name="partner_tipologia" data-required-for="partner">
                                        <option value="">-- Seleziona la tipologia partner --</option>
                                        <option value="AZIENDA_FSL" <?= ($_POST['partner_tipologia'] ?? '') === 'AZIENDA_FSL' ? 'selected' : '' ?>>Partner FSL</option>
                                        <option value="PARTNER_VR" <?= ($_POST['partner_tipologia'] ?? '') === 'PARTNER_VR' ? 'selected' : '' ?>>Partner VR</option>
                                    </select>
                                    <div class="form-text">Scegli FSL per aziende e percorsi scuola-lavoro; scegli VR per servizi ed esperienze in realtà virtuale.</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="partner_cf_piva" class="form-label">CF/PIVA</label>
                                        <input type="text" class="form-control" id="partner_cf_piva" name="partner_cf_piva">
                                    </div>
                                    <div class="col-md-4 mb-3" id="partnerReaGroup">
                                        <label for="partner_cod_rea" class="form-label">Codice REA *</label>
                                        <input type="text" class="form-control" id="partner_cod_rea" name="partner_cod_rea" data-required-for="partner">
                                        <div class="form-text">Obbligatorio per Partner FSL e Partner VR.</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="partner_comune" class="form-label"><?= $t['citta'] ?></label>
                                        <input type="text" class="form-control" id="partner_comune" name="partner_comune">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="partner_indirizzo" class="form-label"><?= $t['indirizzo'] ?></label>
                                    <textarea class="form-control" id="partner_indirizzo" name="partner_indirizzo" rows="2"></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="partner_regione" class="form-label">Regione *</label>
                                        <select class="form-select" id="partner_regione" name="partner_regione" data-required-for="partner" onchange="populateProvince('partner_regione', 'partner_provincia')">
                                            <option value="">-- Seleziona una Regione --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="partner_provincia" class="form-label">Provincia *</label>
                                        <select class="form-select" id="partner_provincia" name="partner_provincia" data-required-for="partner">
                                            <option value="">-- Seleziona una Provincia --</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="partner_coordinate_gps" class="form-label">Coordinate GPS</label>
                                    <input type="text" class="form-control" id="partner_coordinate_gps" name="partner_coordinate_gps" placeholder="Es. 45.4642, 9.1900">
                                </div>

                                <div class="mb-3">
                                    <label for="partner_telefono" class="form-label"><?= $t['telefono'] ?></label>
                                    <input type="tel" class="form-control" id="partner_telefono" name="partner_telefono">
                                </div>

                                <div class="mb-3">
                                    <label for="partner_descrizione" class="form-label"><?= $t['descrizione'] ?></label>
                                    <textarea class="form-control" id="partner_descrizione" name="partner_descrizione" rows="3"></textarea>
                                </div>
                            </div>

                            <!-- Campi Utente -->
                            <div id="utenteFields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="utente_nome" class="form-label"><?= $t['nome'] ?> *</label>
                                        <input type="text" class="form-control" id="utente_nome" name="utente_nome" data-required-for="utente">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="utente_cognome" class="form-label"><?= $t['cognome'] ?> *</label>
                                        <input type="text" class="form-control" id="utente_cognome" name="utente_cognome" data-required-for="utente">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="utente_tipo" class="form-label"><?= $t['tipo_utente'] ?> *</label>
                                    <select class="form-select" id="utente_tipo" name="utente_tipo" data-required-for="utente">
                                        <option value="">-- Seleziona --</option>
                                        <option value="studente">Studente</option>
                                        <option value="genitore">Genitore</option>
                                        <option value="docente">Docente</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="utente_data_nascita" class="form-label"><?= $t['data_nascita'] ?></label>
                                    <input type="date" class="form-control" id="utente_data_nascita" name="utente_data_nascita">
                                </div>

                                <div class="mb-3">
                                    <label for="utente_telefono" class="form-label"><?= $t['telefono'] ?></label>
                                    <input type="tel" class="form-control" id="utente_telefono" name="utente_telefono">
                                </div>
                            </div>

                            <!-- Campi comuni -->
                            <div class="mb-3">
                                <label for="email" class="form-label"><?= $t['email'] ?> *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label"><?= $t['password'] ?> *</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                <small class="form-text text-muted">Minimo 8 caratteri</small>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label"><?= $t['confirm_password'] ?> *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3"><?= $t['register'] ?></button>
                        </form>

                        <div class="text-center">
                            <p class="mb-0"><?= $t['has_account'] ?> <a href="login.php?lang=<?= $lang ?>"><?= $t['login'] ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="province_regioni.js"></script>
    <script>
        function toggleFields() {
            const userType = document.getElementById('user_type').value;
            document.getElementById('istitutoFields').style.display = userType === 'istituto' ? 'block' : 'none';
            document.getElementById('utenteFields').style.display = userType === 'utente' ? 'block' : 'none';
            document.getElementById('partnerFields').style.display = userType === 'partner' ? 'block' : 'none';

            // Attiva required solo per i campi del blocco visibile
            document.querySelectorAll('[data-required-for]').forEach(function(field) {
                field.required = field.getAttribute('data-required-for') === userType;
            });

            if (userType === 'istituto') {
                initRegionProvinceSelects('istituto_regione', 'istituto_provincia');
            }
            if (userType === 'partner') {
                initRegionProvinceSelects('partner_regione', 'partner_provincia');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            initRegionProvinceSelects('istituto_regione', 'istituto_provincia');
            initRegionProvinceSelects('partner_regione', 'partner_provincia');
            toggleFields();
        });
    </script>

    <?php if ($showSuccessPopup): ?>
    <script>
        alert('Registrazione completata. Ti abbiamo inviato una email di conferma.');
    </script>
    <?php endif; ?>
</body>
</html>
