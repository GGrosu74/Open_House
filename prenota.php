<?php
require_once 'config.php';

requireRole(['utente','istituto','partner']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attivita_id = intval($_POST['attivita_id'] ?? 0);
    $lang = $_POST['lang'] ?? 'it';
    $session_user_id = (int)($_SESSION['user_id'] ?? 0);
    $session_user_type = $_SESSION['user_type'] ?? '';
    $modalita = $_POST['modalita_fruizione'] ?? 'casa';
    $partner_vr_id = intval($_POST['partner_vr_id'] ?? 0);
    $numero_partecipanti = intval($_POST['numero_partecipanti'] ?? 1);
    $note = trim($_POST['note'] ?? '');
    $qr_code = strtoupper(bin2hex(random_bytes(6)));
    $prenotante_email = $_SESSION['user_email'] ?? '';
    $is_ente_prenotante = in_array($session_user_type, ['istituto', 'partner'], true);
    $utente_id = $is_ente_prenotante ? null : $session_user_id;
    $istituto_prenotante_id = $is_ente_prenotante ? $session_user_id : null;

    if (!in_array($modalita, ['casa', 'arena_fisica', 'arena_mobile'], true)) {
        http_response_code(400); exit('Modalità non valida.');
    }

    if (!$is_ente_prenotante && $session_user_type !== 'utente') {
        $_SESSION['error'] = 'Account non abilitato alla prenotazione';
        header('Location: attivita_dettaglio.php?id=' . $attivita_id . '&lang=' . $lang);
        exit;
    }

    try {
        if ($numero_partecipanti<1 || $numero_partecipanti>10000 || mb_strlen($note)>4000) throw new RuntimeException('Dati prenotazione non validi.');
        if ($modalita==='casa') $partner_vr_id=0;
        else {
            $v=$pdo->prepare('SELECT Tipologia,Stato_Validazione FROM istituti_e_partner WHERE ID_Ente=?');
            $v->execute([$partner_vr_id]);$venue=$v->fetch();
            $types=$modalita==='arena_mobile'?['ARENA_MOBILE']:['ARENA_VR','PARTNER_VR'];
            if(!$venue || (int)$venue['Stato_Validazione']!==1 || !in_array($venue['Tipologia'],$types,true)) throw new RuntimeException('Seleziona una struttura VR approvata e compatibile.');
        }
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT a.ID_Attivita as id, a.Titolo as titolo, a.Data_Ora as data_ora, a.Max_Posti as max_partecipanti, a.Stato as stato, i.Ragione_Sociale as organizzatore_nome, i.Email as organizzatore_email FROM attivita_eventi a JOIN istituti_e_partner i ON a.FK_Ente_Organizzatore = i.ID_Ente WHERE a.ID_Attivita = ? AND i.Stato_Validazione=1 FOR UPDATE");
        $stmt->execute([$attivita_id]);
        $attivita = $stmt->fetch();

        if (!$attivita || $attivita['stato'] !== 'pubblicata' || strtotime($attivita['data_ora'])<=time()) {
            throw new RuntimeException('Attività non disponibile');
        }

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(numero_partecipanti), 0) as prenotazioni_count FROM prenotazioni WHERE attivita_id = ? AND stato = 'confermata'");
        $stmt->execute([$attivita_id]);
        $prenotazioni_count = (int)($stmt->fetch()['prenotazioni_count'] ?? 0);

        if (($prenotazioni_count + $numero_partecipanti) > (int)$attivita['max_partecipanti']) {
            throw new RuntimeException('Posti esauriti');
        }

        if ($is_ente_prenotante) {
            $stmt = $pdo->prepare("SELECT id,stato FROM prenotazioni WHERE istituto_prenotante_id = ? AND attivita_id = ? ORDER BY id");
            $stmt->execute([$session_user_id, $attivita_id]);
        } else {
            $stmt = $pdo->prepare("SELECT id,stato FROM prenotazioni WHERE utente_id = ? AND attivita_id = ? ORDER BY id");
            $stmt->execute([$session_user_id, $attivita_id]);
        }

        $existing=$stmt->fetchAll();
        if (array_filter($existing,fn($row)=>$row['stato']!=='cancellata')) {
            throw new RuntimeException('Hai già prenotato questa attività');
        }

        $notePrenotazione = $note !== '' ? $note : ($is_ente_prenotante ? 'Prenotazione effettuata da ente' : 'Prenotazione effettuata da utente finale');

        if ($existing) {
            $stmt=$pdo->prepare("UPDATE prenotazioni SET stato='confermata',note=?,modalita_fruizione=?,partner_vr_id=?,numero_partecipanti=?,qr_code=? WHERE id=?");
            $stmt->execute([$notePrenotazione,$modalita,$partner_vr_id?:null,$numero_partecipanti,$qr_code,$existing[0]['id']]);
        } else {
        $stmt = $pdo->prepare("INSERT INTO prenotazioni (utente_id, attivita_id, stato, note, modalita_fruizione, partner_vr_id, istituto_prenotante_id, numero_partecipanti, qr_code, fsl_ore) VALUES (?, ?, 'confermata', ?, ?, ?, ?, ?, ?, NULL)");
        $stmt->execute([
            $utente_id,
            $attivita_id,
            $notePrenotazione,
            $modalita,
            $partner_vr_id > 0 ? $partner_vr_id : null,
            $istituto_prenotante_id,
            $numero_partecipanti,
            $qr_code
        ]);

        }
        $pdo->commit();

        $organizerEmail = $attivita['organizzatore_email'] ?? '';
        $subject = 'Prenotazione confermata - ' . $attivita['titolo'];
        $bookingDetails = '<h2>Prenotazione confermata</h2>'
            . '<p><strong>Attività:</strong> ' . htmlspecialchars($attivita['titolo']) . '</p>'
            . '<p><strong>Data e ora:</strong> ' . date('d/m/Y H:i', strtotime($attivita['data_ora'])) . '</p>'
            . '<p><strong>Prenotante:</strong> ' . htmlspecialchars($is_ente_prenotante ? 'Ente prenotante' : 'Utente finale') . '</p>'
            . '<p><strong>Email prenotante:</strong> ' . htmlspecialchars($prenotante_email) . '</p>'
            . '<p><strong>Modalità:</strong> ' . htmlspecialchars($modalita) . '</p>'
            . '<p><strong>Partecipanti:</strong> ' . (int)$numero_partecipanti . '</p>'
            . '<p><strong>Note:</strong><br>' . nl2br(htmlspecialchars($notePrenotazione)) . '</p>'
            . '<p><strong>QR code:</strong> ' . htmlspecialchars($qr_code) . '</p>';

        $sent=sendHtmlEmail($prenotante_email, $subject, $bookingDetails);
        sendHtmlEmail($organizerEmail, 'Nuova prenotazione - ' . $attivita['titolo'], $bookingDetails);

        $_SESSION['success'] = $sent ? 'Prenotazione confermata. Conferma affidata al servizio email.' : 'Prenotazione confermata. Email non disponibile: conserva il codice nella dashboard.';
        $_SESSION['booking_popup'] = [
            'titolo' => $attivita['titolo'],
            'organizzatore' => $attivita['organizzatore_nome'],
            'numero_partecipanti' => $numero_partecipanti,
            'modalita' => $modalita,
            'qr_code' => $qr_code
        ];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = 'Errore durante la prenotazione';
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: attivita_dettaglio.php?id=' . $attivita_id . '&lang=' . $lang);
    exit;
}

header('Location: index.php');
exit;
?>
