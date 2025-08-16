<!-- src/Views/user/edit.php -->

<?php
$pageTitle = 'Meine Daten bearbeiten';
require __DIR__ . '/../partials/header.php';

// --- Neu: Session-Meldungen (für Redirects) übernehmen und löschen ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!empty($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (!empty($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<div class="admin-main">
    <div class="content-box">
        <?php if (!empty($section) && $section === 'password'): ?>
            <h2>Mein Passwort ändern</h2>
        <?php else: ?>
            <h2>Meine Daten bearbeiten</h2>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php
        // Nur anzeigen, wenn wir NICHT auf der Passwort-Seite sind
        $section = $section ?? ($_GET['section'] ?? '');
        if ($section !== 'password') {
            // Controller kann $pendingEmail/$pendingExpires/$resendUrl setzen.
            $pendingEmail = $pendingEmail ?? null;
            $pendingExpires = $pendingExpires ?? null;
            $resendUrl = $resendUrl ?? '/user/resend-confirm-email';

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
                        <a class="btn btn-secondary" href="<?= htmlspecialchars($resendUrl . '?uid=' . urlencode($user['id'] ?? '')) ?>" style="margin-left:8px;padding:6px 10px;text-decoration:none;">Erneut senden</a>
                    </p>
                </div>
            <?php endif;
        }
        ?>

        <?php if (!empty($user)): ?>

            <?php
            // Bestimme Kontext
            if (session_status() === PHP_SESSION_NONE) session_start();
            $currentUserId = $_SESSION['user_id'] ?? 0;
            $currentIsAdmin = !empty($_SESSION['is_admin']);
            $isOwnProfile = ($currentUserId == $user['id']);
            // $section wird vom Controller gesetzt; fallback auf query param falls vorhanden
            $section = $section ?? ($_GET['section'] ?? '');
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
                <!-- Profil-Formular: jetzt inkl. Vorname/Nachname -->
                <form method="post" action="" class="admin-form">
                    <div class="form-group">
                        <label for="first_name">Vorname:</label>
                        <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Nachname:</label>
                        <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">E-Mail:</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
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
