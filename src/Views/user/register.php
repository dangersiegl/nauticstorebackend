<!-- src/Views/user/register.php -->

<?php 
// Titel für den header.php
$pageTitle = 'Registrieren';

// Header einbinden
require __DIR__ . '/../partials/header.php'; 
?>

<h2>Registrieren</h2>

<?php if (!empty($error)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form method="post" action="/register">
    <label for="email">E-Mail:</label>
    <input type="email" name="email" id="email" required>

    <br><br>
    <label for="password">Passwort:</label>
    <input type="password" name="password" id="password" required>

    <br><br>
    <button type="submit">Registrieren</button>
</form>

<p>
    <a href="/login">Zum Login</a>
</p>

<?php 
// Footer einbinden
require __DIR__ . '/../partials/footer.php'; 
?>
