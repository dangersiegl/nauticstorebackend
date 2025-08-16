<!-- src/Views/user/login_totp.php -->

<?php
$pageTitle = 'TOTP-Code Eingabe';
require __DIR__ . '/../partials/header.php';
?>

<div class="admin-main login-page">
    <div class="login-box">
        <h2>Zwei-Faktor-Authentifizierung</h2>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" action="/login/totp" class="login-form">
            <div class="form-group">
                <label for="totp">TOTP-Code:</label>
                <input type="text" name="totp" id="totp" maxlength="6" pattern="\d{6}" required>
            </div>

            <button type="submit" class="btn btn-primary">Verifizieren</button>
        </form>

        <p class="help-text">
            Öffne deine Authenticator-App und gib den sechsstelligen Code ein.
        </p>
    </div>
</div>

<?php 
require __DIR__ . '/../partials/footer.php';
?>
