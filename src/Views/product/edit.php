<!-- src/Views/product/edit.php -->

<?php
$pageTitle = 'Produkt bearbeiten'; // Wird im <title>-Tag (header.php) verwendet
require __DIR__ . '/../partials/header.php';
?>

<h2>Produkt bearbeiten</h2>

<form method="post" action="?controller=product&action=edit&id=<?php echo $product['id']; ?>">
    <label for="name">Name:</label>
    <input type="text" name="name" id="name" required
           value="<?php echo htmlspecialchars($product['name']); ?>">

    <br><br>
    <label for="description">Beschreibung:</label>
    <textarea name="description" id="description"><?php
        echo htmlspecialchars($product['description']);
    ?></textarea>

    <br><br>
    <label for="price">Preis (EUR):</label>
    <input type="number" step="0.01" name="price" id="price" required
           value="<?php echo htmlspecialchars($product['price']); ?>">

    <br><br>
    <button type="submit">Aktualisieren</button>
</form>

<?php
require __DIR__ . '/../partials/footer.php';
?>
