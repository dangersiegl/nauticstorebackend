<!-- src/Views/product/list.php -->

<?php
// Individueller Seitentitel
$pageTitle = 'Produktliste';

// Header einbinden
require __DIR__ . '/../partials/header.php';
?>

<div class="admin-main">
    <h2>Produkte</h2>

    <div class="admin-actions">
        <?php if (!empty($_SESSION['is_admin'])): ?>
            <!-- Button oder Link zum Anlegen neuer Produkte -->
            <a href="/artikel/neu" class="btn btn-primary">Neues Produkt anlegen</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($products)): ?>
        <table border="1" class="table table-striped" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 8px; background-color: #f2f2f2;">ID</th>
                    <th style="padding: 8px; background-color: #f2f2f2;">Name</th>
                    <th style="padding: 8px; background-color: #f2f2f2;">Preis (EUR)</th>
                    <th style="padding: 8px; background-color: #f2f2f2;">Beschreibung</th>
                    <?php if (!empty($_SESSION['is_admin'])): ?>
                        <th style="padding: 8px; background-color: #f2f2f2;">Aktionen</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td style="padding: 8px;">
                            <?php echo htmlspecialchars($p['id']); ?>
                        </td>
                        <td style="padding: 8px;">
                            <?php echo htmlspecialchars($p['name']); ?>
                        </td>
                        <td style="padding: 8px;">
                            <!-- Preis formatieren, z.B. mit 2 Nachkommastellen -->
                            <?php echo number_format($p['price'], 2, ',', '.'); ?> €
                        </td>
                        <td style="padding: 8px;">
                            <!-- Zeilenumbrüche in der Beschreibung beibehalten -->
                            <?php echo nl2br(htmlspecialchars($p['description'])); ?>
                        </td>
                        <?php if (!empty($_SESSION['is_admin'])): ?>
                            <td style="padding: 8px;">
                                <!-- Bearbeiten und Löschen, 
                                     ggf. an dein eigenes Routing anpassen -->
                                <a href="?controller=product&action=edit&id=<?php echo $p['id']; ?>"
                                   class="btn btn-secondary">
                                   Bearbeiten
                                </a>
                                <a href="?controller=product&action=delete&id=<?php echo $p['id']; ?>"
                                   class="btn btn-danger"
                                   onclick="return confirm('Wirklich löschen?');">
                                   Löschen
                                </a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Keine Produkte vorhanden.</p>
    <?php endif; ?>
</div>

<?php
// Footer einbinden
require __DIR__ . '/../partials/footer.php';
