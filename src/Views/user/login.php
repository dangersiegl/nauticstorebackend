<!-- src/Views/user/login.php -->



<?php

$pageTitle = 'User-Login';

require __DIR__ . '/../partials/header.php';

?>



<div class="admin-main login-page">

    <div class="login-box">

        <h2>Login</h2>



        <?php if (!empty($error)): ?>

            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>

        <?php endif; ?>



        <form method="post" action="/login" class="login-form">

            <div class="form-group">

                <label for="email">E-Mail:</label>

                <input type="email" name="email" id="email" required>

            </div>



            <div class="form-group">

                <label for="password">Passwort:</label>

                <input type="password" name="password" id="password" required>

            </div>



            <button type="submit" class="btn btn-primary">Einloggen</button>

        </form>



        <p class="register-link">

            Noch keinen Account? <a href="/register">Registrieren</a>

        </p>

    </div>

</div>



<?php 

require __DIR__ . '/../partials/footer.php';

?>

