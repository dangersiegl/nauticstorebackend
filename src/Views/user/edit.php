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

        <?php if (!empty($user)): ?>
            <form method="post" action="" class="admin-form">
                <div class="form-group">
                    <label for="email">E-Mail:</label>
                    <input type="email" name="email" id="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="is_admin">Admin?</label>
                    <input type="checkbox" name="is_admin" id="is_admin"
                           <?php if (!empty($user['is_admin'])) echo 'checked'; ?>>
                </div>

                <button type="submit" class="btn btn-primary">Speichern</button>
            </form>
        <?php else: ?>
            <p>Kein Benutzer-Datensatz vorhanden.</p>
        <?php endif; ?>
    </div>
</div>

<?php
require __DIR__ . '/../partials/footer.php';
