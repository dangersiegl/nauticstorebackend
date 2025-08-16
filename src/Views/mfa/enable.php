<!-- src/Views/mfa/enable.php -->
<?php
$pageTitle = 'MFA aktivieren';
require __DIR__ . '/../partials/header.php';
?>

<div class="admin-main">
    <h2>MFA / TOTP aktivieren</h2>

    <p>Scanne den folgenden QR-Code mit deiner Authenticator-App (z.B. Google Authenticator):</p>
    <?php 
        // URL für Google-Chart-API, Grösse 200x200
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($totpUri);
    ?>
    <img src="<?php echo $qrUrl; ?>" alt="TOTP QR-Code">

    <p>Alternativ kannst du folgenden Secret-Key manuell eingeben:<br>
       <strong><?php echo htmlspecialchars($secret); ?></strong></p>

    <p>Nach dem Scannen oder manuellen Eintragen generiert deine App einen 6-stelligen Code, 
       den du beim Login eingeben musst. Falls du gleich testen willst, 
       <a href="/logout">logge dich aus</a> und melde dich neu an.</p>
</div>

<?php
require __DIR__ . '/../partials/footer.php';
