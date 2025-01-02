<!-- src/Views/product/create.php -->

<?php
$pageTitle = 'Neues Produkt anlegen'; // Titel im <title>-Tag (header.php)
require __DIR__ . '/../partials/header.php';
?>

<h2>Neues Produkt anlegen</h2>

<form method="post" action="?controller=product&action=create">
    <label for="name">Name:</label>
    <input type="text" name="name" id="name" required>

    <br><br>
    <label for="description">Beschreibung:</label>
    <textarea name="description" id="description"></textarea>

    <br><br>
    <label for="price">Preis (EUR):</label>
    <input type="number" step="0.01" name="price" id="price" required>

    <br><br>
    <button type="submit">Speichern</button>
</form>

<?php 
require __DIR__ . '/../partials/footer.php'; 
?>
