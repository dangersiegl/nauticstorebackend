<?php
$pageTitle = 'TOTP-Registrierung abschließen';
require __DIR__ . '/../partials/header.php';
?>

<div class="admin-main login-page">
    <div class="login-box">
        <h2>TOTP-Registrierung abschließen</h2>

        <p>Bitte scanne den folgenden QR-Code mit deiner Authenticator-App (z.B. Google Authenticator, Authy, etc.):</p>

        <img src="<?= $qrCodeUrl ?>" alt="TOTP QR-Code">

        <p>Oder gib den folgenden Code manuell in deine Authenticator-App ein:</p>

        <p><strong><?= $data['secret'] ?></strong></p>

        <p>Nachdem du den QR-Code gescannt oder den Code eingegeben hast, kannst du dich anmelden.</p>

        <p><strong>Hinweis:</strong> Der QR-Code und der Secret-Key sind nur für die erstmalige Einrichtung gültig.</p>
        <a href="/login">Zum Login</a>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>