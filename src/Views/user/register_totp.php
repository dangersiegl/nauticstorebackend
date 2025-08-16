<?php
$pageTitle = 'TOTP-Registrierung abschließen';
require __DIR__ . '/../partials/header.php';
?>

<div class="admin-main login-page">
    <div class="login-box">
        <h2>TOTP-Registrierung abschließen</h2>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <p>Bitte scannen Sie den folgenden QR-Code mit Ihrer Authenticator-App (z.B. Google Authenticator, Authy):</p>

        <?php if (!empty($qrCodeUrl)): ?>
            <!-- QR und Logo nebeneinander (flex, wrap für kleine Bildschirme) -->
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-top:8px;margin-bottom:12px;">
                <!-- Logo aus public/assets/logo -->
                <div style="flex:0 0 auto;">
                    <img src="/assets/logo/nauticstore24logo-backend.png" alt="Logo" style="width:80px;height:auto;border-radius:6px;border:1px solid rgba(0,0,0,0.05);background:#fff;padding:6px;">
                </div>

                <!-- QR-Code -->
                <div style="flex:0 0 auto;">
                    <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="TOTP QR-Code" style="width:200px;height:200px;border:1px solid #ddd;padding:4px;background:#fff;">
                </div>
            </div>
        <?php else: ?>
            <p><em>QR-Code konnte nicht erzeugt werden.</em></p>
        <?php endif; ?>

        <p>Oder geben Sie den folgenden Secret-Key manuell in Ihre Authenticator-App ein:</p>
        <p><strong><?php echo htmlspecialchars($data['secret'] ?? ''); ?></strong></p>

        <form method="post" action="/register" class="login-form" style="margin-top:15px;">
            <div class="form-group">
                <label for="totp">TOTP-Code (6-stellig):</label>
                <input type="text" name="totp" id="totp" maxlength="6" pattern="\d{6}" required>
            </div>

            <button type="submit" class="btn btn-primary">Weiter</button>
        </form>

        <p style="margin-top:12px;">
            Falls Sie zurück zum Registrierungsformular möchten, <a href="/register">klicken Sie hier</a>.
        </p>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>