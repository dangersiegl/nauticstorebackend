<?php
$pageTitle = 'Neuen Benutzer anlegen';
require __DIR__ . '/../partials/header.php';
?>

<div class="admin-main login-page">
    <div class="login-box">
        <h2>Neuen Benutzer anlegen</h2>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- 
            action="/user/store" geht davon aus, 
            dass deine Route in index.php (bzw. customRoutes) 
            so konfiguriert ist, dass /user/store -> UserController->store() aufruft.
        -->
        <form method="post" action="/user/store" class="login-form">
            
            <div class="form-group">
                <label for="email">E-Mail:</label>
                <input type="email" name="email" id="email" required>
            </div>

            <div class="form-group">
                <label for="password">Passwort:</label>
                <input type="password" name="password" id="password" required>
            </div>

            <button type="submit" class="btn btn-primary">Benutzer anlegen</button>
        </form>
    </div>
</div>

<?php
require __DIR__ . '/../partials/footer.php';
?>
