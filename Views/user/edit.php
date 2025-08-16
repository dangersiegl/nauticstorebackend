<?php
// ...existing code: Header, Fehler/Success-Ausgaben ...

// Sicherstellen, dass Session verfügbar ist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Stelle sicher, dass $section gesetzt ist (Controller kann es liefern)
$section = $section ?? ($_GET['section'] ?? '');

// Nur anzeigen, wenn wir NICHT auf der Passwort-Seite sind
if ($section !== 'password') {

    // --- bestehende Pending-Abfrage / Anzeige (unverändert, nur eingerückt) ---
    // 1) Versuche, die Ziel-User-ID zu ermitteln (Controller-$user, GET uid, Route parsing)
    $uid = null;
    if (!empty($user['id'])) {
        $uid = (int)$user['id'];
    } elseif (!empty($_GET['uid'])) {
        $uid = (int)$_GET['uid'];
    } else {
        $route = $_GET['route'] ?? '';
        $routeParts = array_values(array_filter(explode('/', trim($route, '/'))));
        if (isset($routeParts[0]) && isset($routeParts[1]) && isset($routeParts[2]) && strtolower($routeParts[0]) === 'user' && strtolower($routeParts[1]) === 'edit' && is_numeric($routeParts[2])) {
            $uid = (int)$routeParts[2];
        } else {
            $uriParts = array_values(array_filter(explode('/', parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH))));
            for ($i = 0; $i < count($uriParts) - 2; $i++) {
                if (strtolower($uriParts[$i]) === 'user' && strtolower($uriParts[$i+1]) === 'edit' && is_numeric($uriParts[$i+2])) {
                    $uid = (int)$uriParts[$i+2];
                    break;
                }
            }
        }
    }

    // 2) Falls keine UID, versuche Session-User
    if (empty($uid) && !empty($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];
    }

    // 3) Fallback-Variablen
    $pendingEmail = null;
    $pendingExpires = null;
    $resendUrl = '/user/resend-confirm-email';

    // 4) Wenn UID vorhanden, hole letzten email_resets-Eintrag
    if (!empty($uid)) {
        $pdo = null;
        // wie im Controller: versuche verschiedene Database-Access-Punkte
        if (class_exists('\App\Models\Database')) {
            $cls = '\App\Models\Database';
            if (method_exists($cls, 'getConnection')) {
                try { $pdo = $cls::getConnection(); } catch (\Throwable $e) { $pdo = null; }
            }
            if ($pdo === null && method_exists($cls,'getInstance')) {
                try { $inst = $cls::getInstance(); if (is_object($inst) && method_exists($inst,'getConnection')) $pdo = $inst->getConnection(); } catch (\Throwable $e) { $pdo = null; }
            }
        }
        if ($pdo === null && class_exists('Database')) {
            $cls = 'Database';
            if (method_exists($cls, 'getConnection')) {
                try { $pdo = $cls::getConnection(); } catch (\Throwable $e) { $pdo = null; }
            }
            if ($pdo === null && method_exists($cls,'getInstance')) {
                try { $inst = $cls::getInstance(); if (is_object($inst) && method_exists($inst,'getConnection')) $pdo = $inst->getConnection(); } catch (\Throwable $e) { $pdo = null; }
            }
        }
        if ($pdo === null && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof \PDO) $pdo = $GLOBALS['pdo'];
        if ($pdo === null && !empty($GLOBALS['db']) && $GLOBALS['db'] instanceof \PDO) $pdo = $GLOBALS['db'];
        if ($pdo === null && !empty($GLOBALS['database']) && $GLOBALS['database'] instanceof \PDO) $pdo = $GLOBALS['database'];

        if ($pdo instanceof \PDO) {
            try {
                $stmt = $pdo->prepare("SELECT id, new_email, token, created_at FROM email_resets WHERE user_id = :uid ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([':uid' => $uid]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!empty($row['new_email'])) {
                    $pendingEmail = $row['new_email'];
                    $createdTs = strtotime($row['created_at'] ?? '');
                    $expiresAt = ($createdTs !== false) ? ($createdTs + 48*3600) : null; // 48 Stunden
                    $pendingExpires = ($expiresAt !== null) ? max(0, $expiresAt - time()) : null;
                    // setze resendUrl (optional mit uid)
                    $resendUrl = '/user/resend-confirm-email';
                }
            } catch (\Throwable $e) {
                // optional logging: error_log($e->getMessage());
                $pendingEmail = null;
            }
        } else {
            // Keine DB-Verbindung — nichts anzeigen
            $pendingEmail = null;
        }
    }

    // 5) Anzeige, falls pending vorhanden
    if (!empty($pendingEmail)): ?>
        <div class="alert alert-info" style="margin:10px 0;padding:12px;border:1px solid #bcd;font-size:0.95rem;background:#f7fbff;">
            <p style="margin:0 0 8px;">
                <strong>Bestätigung ausstehend:</strong><br>
                Eine Bestätigungsmail zur Änderung Ihrer E‑Mail‑Adresse wurde an <strong><?= htmlspecialchars($pendingEmail) ?></strong> gesendet.
            </p>
            <?php if ($pendingExpires !== null): ?>
                <p style="margin:0 0 8px;">Der Bestätigungslink läuft in ca. <strong><?= ceil($pendingExpires/3600) ?> Stunden</strong> ab.</p>
            <?php endif; ?>
            <p style="margin:0;">
                Falls Sie die Mail nicht erhalten haben, können Sie sie erneut anfordern:
                <a class="btn btn-secondary" href="<?= htmlspecialchars($resendUrl . '?uid=' . urlencode($uid)) ?>" style="display:inline-block;margin-left:8px;padding:6px 10px;text-decoration:none;">Erneut senden</a>
            </p>
        </div>
    <?php endif;

} // Ende: nur anzeigen wenn nicht password

<?php
// ...existing code: Formular zur Bearbeitung der Profildaten ...

// Ende der View