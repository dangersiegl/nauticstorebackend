<!-- src/Views/user/edit.php -->

<?php
$pageTitle = 'Benutzer bearbeiten';
require __DIR__ . '/../partials/header.php';
?>

<div class="admin-main">
    <div class="content-box">
        <h2>Benutzer bearbeiten</h2>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($user)): ?>

            <?php
            // Bestimme Kontext
            if (session_status() === PHP_SESSION_NONE) session_start();
            $currentUserId = $_SESSION['user_id'] ?? 0;
            $currentIsAdmin = !empty($_SESSION['is_admin']);
            $isOwnProfile = ($currentUserId == $user['id']);
            $section = $_GET['section'] ?? '';
            ?>

            <?php if ($section === 'password'): ?>
                <!-- Passwort-Formular -->
                <form method="post" action="" class="admin-form">
                    <div class="form-group">
                        <label for="current_password">Aktuelles Passwort:</label>
                        <input type="password" name="current_password" id="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Neues Passwort:</label>
                        <input type="password" name="new_password" id="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password_confirm">Neues Passwort (Wdh.):</label>
                        <input type="password" name="new_password_confirm" id="new_password_confirm" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Passwort ändern</button>
                </form>

            <?php else: ?>
                <!-- Profil-Formular -->
                <form method="post" action="" class="admin-form">
                    <div class="form-group">
                        <label for="email">E-Mail:</label>
                        <input type="email" name="email" id="email"
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <?php if ($currentIsAdmin && !$isOwnProfile): ?>
                        <div class="form-group">
                            <label for="is_admin">Admin?</label>
                            <input type="checkbox" name="is_admin" id="is_admin"
                                <?php if (!empty($user['is_admin'])) echo 'checked'; ?>>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary">Speichern</button>
                </form>
            <?php endif; ?>

        <?php else: ?>
            <p>Kein Benutzer-Datensatz vorhanden.</p>
        <?php endif; ?>
    </div>
</div>

<?php
require __DIR__ . '/../partials/footer.php';
