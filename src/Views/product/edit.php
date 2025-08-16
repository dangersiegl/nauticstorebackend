<!-- src/Views/product/edit.php -->

<?php
$pageTitle = 'Produkt bearbeiten'; // Wird im <title>-Tag (header.php) verwendet
require __DIR__ . '/../partials/header.php';
?>

<div class="content-box">
    <h2>Produkt bearbeiten</h2>

    <form method="post" action="?controller=product&action=edit&id=<?php echo $product['id']; ?>" class="admin-form">
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" required
                   value="<?php echo htmlspecialchars($product['name']); ?>">
        </div>

        <div class="form-group">
            <label for="description">Beschreibung:</label>
            <textarea name="description" id="description"><?php
                echo htmlspecialchars($product['description']);
            ?></textarea>
        </div>

        <div class="form-group">
            <label for="price">Preis (EUR):</label>
            <input type="number" step="0.01" name="price" id="price" required
                   value="<?php echo htmlspecialchars($product['price']); ?>">
        </div>

        <button type="submit" class="btn btn-primary">Aktualisieren</button>
    </form>
</div>

<?php
require __DIR__ . '/../partials/footer.php';
?>
