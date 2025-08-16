<!-- src/Views/mfa/enable.php -->
<?php
$pageTitle = 'MFA aktivieren';
require __DIR__ . '/../partials/header.php';
?>
<script>
(function(){
    // Wenn kein Font-Awesome Stylesheet geladen ist, nachladen
    if (!document.querySelector('link[href*="font-awesome"]') && !document.querySelector('link[href*="fontawesome"]') ) {
        var l = document.createElement('link');
        l.rel = 'stylesheet';
        l.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
        l.crossOrigin = 'anonymous';
        document.head.appendChild(l);
    }

    // Kleines Fallback-CSS, falls Icons wegen Stil-Regeln nicht sichtbar sind
    var cssId = 'fa-fallback-styles';
    if (!document.getElementById(cssId)) {
        var s = document.createElement('style');
        s.id = cssId;
        s.innerHTML = '\
            .nav-icon { display: inline-block; width: 28px; text-align: center; font-size: 1.1rem; } \
            .nav-icon i, .nav-icon::before { font-style: normal; } \
        ';
        document.head.appendChild(s);
    }
})();
</script>

<div class="content-box">
    <h2>Multifaktor-Authentifizierung MFA / TOTP</h2>

    <?php
    // Zeige globale Session-Messages falls vorhanden
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!empty($_SESSION['success_message'])) {
        echo '<div class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
    if (!empty($_SESSION['error_message'])) {
        echo '<div class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }

    // Lokale Fehlervariablen (aus Controller)
    if (!empty($error)) {
        echo '<div class="error-message">' . htmlspecialchars($error) . '</div>';
    }
    ?>

    <?php if (!empty($mfa_enabled)): ?>
        <p style="color:green;font-weight:600;">MFA ist aktiviert.</p>

        <p>Wenn Sie MFA deaktivieren, werden Sie wieder nur mit E-Mail/Passwort angemeldet. Diese Aktion kann die Sicherheit Ihres Kontos verringern.</p>

        <form method="post" class="admin-form" action="/user/mfa/disable" onsubmit="return confirm('Möchten Sie die Zwei-Faktor-Authentifizierung wirklich deaktivieren? Diese Aktion kann die Sicherheit Ihres Kontos verringern.');" style="margin-top:12px;">
            <div class="form-group">
                <label for="password">Bitte bestätigen Sie Ihr Passwort:</label>
                <input type="password" name="password" id="password" class="admin-input" required>
            </div>

            <button type="submit" class="btn btn-primary">MFA deaktivieren</button>
        </form>

    <?php else: ?>
        <p>Scannen Sie den folgenden QR-Code mit Ihrer Authenticator-App (z.B. Google Authenticator):</p>
        <?php 
            $qrUrl = !empty($qrCodeUrl) ? $qrCodeUrl : (!empty($totpUri) ? "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($totpUri) : '');
        ?>
        <?php if ($qrUrl): ?>
            <img src="<?php echo htmlspecialchars($qrUrl); ?>" alt="TOTP QR-Code" style="max-width:200px;border:1px solid #ddd;padding:6px;background:#fff;">
        <?php else: ?>
            <p><em>QR-Code konnte nicht erzeugt werden.</em></p>
        <?php endif; ?>

        <p>Alternativ können Sie folgenden Secret-Key manuell eingeben:<br>
           <strong><?php echo htmlspecialchars($secret ?? ''); ?></strong></p>

        <p>Nach dem Scannen oder manuellen Eintragen generiert Ihre App einen 6-stelligen Code, den Sie beim Login eingeben müssen.</p>

        <!-- Formular zur Bestätigung des TOTP-Codes -->
        <form method="post" action="/user/mfa/enable" class="admin-form" style="margin-top:12px;">
            <div class="form-group">
                <label for="totp">TOTP-Code (6-stellig):</label>
                <input type="text" name="totp" id="totp" class="admin-input" maxlength="6" pattern="\d{6}" required>
            </div>
            <button type="submit" class="btn btn-primary">Aktivieren</button>
        </form>
    <?php endif; ?>
</div>

<?php
require __DIR__ . '/../partials/footer.php';
?>
