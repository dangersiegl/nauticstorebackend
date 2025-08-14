<!-- src/Views/user/login_totp.php -->
<?php
// Titel festlegen und Header laden
$pageTitle = 'TOTP Login';
require __DIR__ . '/../partials/header.php';
?>

<div class="admin-main login-page">
    <div class="login-box">
        <h2>Zwei-Faktor-Authentifizierung</h2>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" action="/login" class="login-form">
            <div class="form-group">
                <label for="totp">TOTP-Code:</label>
                <input type="text" name="totp" id="totp" required>
            </div>

            <button type="submit" class="btn btn-primary">Einloggen</button>
        </form>
    </div>
</div>

<?php
require __DIR__ . '/../partials/footer.php';
?>

