<!-- src/Views/user/register.php -->

<?php 
// Titel für den header.php
$pageTitle = 'Registrieren';

// Header einbinden
require __DIR__ . '/../partials/header.php'; 
?>

<div class="admin-main login-page">
    <div class="login-box">
        <h2>Registrieren</h2>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" action="/register" class="login-form">
            <div class="form-group">
                <label for="email">E-Mail:</label>
                <input type="email" name="email" id="email" required>
            </div>

            <div class="form-group">
                <label for="password">Passwort:</label>
                <input type="password" name="password" id="password" required>
            </div>

            <button type="submit" class="btn btn-primary">Registrieren</button>
        </form>

        <p class="register-link">
            <a href="/login">Zum Login</a>
        </p>
    </div>
</div>

<?php 
// Footer einbinden
require __DIR__ . '/../partials/footer.php'; 
?>
