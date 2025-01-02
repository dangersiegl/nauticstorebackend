<!-- src/Views/product/list.php -->

<?php
// Individueller Seitentitel
$pageTitle = 'Produktliste';

// Header einbinden
require __DIR__ . '/../partials/header.php';
?>

<h2>Produkte</h2>
<p>
    <?php if (!empty($_SESSION['is_admin'])): ?>
        <a href="artikel/neu">Neues Produkt anlegen</a>
    <?php endif; ?>
</p>

<?php if (!empty($products)): ?>
    <ul>
        <?php foreach ($products as $p): ?>
            <li>
                <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                (<?php echo number_format($p['price'], 2); ?> €)
                <br>
                <?php echo nl2br(htmlspecialchars($p['description'])); ?>
                <br>
                <?php if (!empty($_SESSION['is_admin'])): ?>
                    <a href="?controller=product&action=edit&id=<?php echo $p['id']; ?>">Bearbeiten</a> |
                    <a href="?controller=product&action=delete&id=<?php echo $p['id']; ?>"
                       onclick="return confirm('Wirklich löschen?');">Löschen</a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Keine Produkte vorhanden.</p>
<?php endif; ?>

<?php
// Footer einbinden
require __DIR__ . '/../partials/footer.php';
?>
