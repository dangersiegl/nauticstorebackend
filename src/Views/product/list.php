<!-- src/Views/product/list.php -->

<?php
// Individueller Seitentitel
$pageTitle = 'Produktliste';

// Header einbinden
require __DIR__ . '/../partials/header.php';
?>

<div class="content-box">
    <h2>Produkte</h2>

    <div class="admin-actions">
        <?php if (!empty($_SESSION['is_admin'])): ?>
            <!-- Button oder Link zum Anlegen neuer Produkte -->
            <a href="/artikel/neu" class="btn btn-primary">Neues Produkt anlegen</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($products) && is_array($products)): ?>
        <table border="1" class="admin-table" style="width: 100%; border-collapse: collapse;">
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
                <?php foreach ($products as $p): 
                    // Sichere Zugriffe mit Fallbacks
                    $id = $p['id'] ?? '';
                    $name = $p['name_de'] ?? $p['name_en'] ?? '(kein Name)';
                    // Beschreibung: kurze bevorzugen, dann lange; deutsch zuerst
                    $description = $p['short_description_de'] ?? $p['description_de'] 
                                 ?? $p['short_description_en'] ?? $p['description_en'] ?? '';
                    // Price ggf. vorhanden (Tabelle hat aktuell kein price-Feld)
                    $priceRaw = $p['price'] ?? null;
                    $priceDisplay = '-';
                    if ($priceRaw !== null && $priceRaw !== '') {
                        // nur formatieren, wenn numerisch
                        if (is_numeric($priceRaw)) {
                            $priceDisplay = number_format((float)$priceRaw, 2, ',', '.') . ' €';
                        } else {
                            $priceDisplay = htmlspecialchars((string)$priceRaw);
                        }
                    }
                ?>
                    <tr>
                        <td style="padding: 8px;"><?php echo htmlspecialchars((string)$id); ?></td>
                        <td style="padding: 8px;"><?php echo htmlspecialchars($name); ?></td>
                        <td style="padding: 8px;"><?php echo $priceDisplay; ?></td>
                        <td style="padding: 8px;"><?php echo $description !== '' ? nl2br(htmlspecialchars($description)) : '&mdash;'; ?></td>
                        <?php if (!empty($_SESSION['is_admin'])): ?>
                            <td style="padding: 8px;">
                                <!-- Bearbeiten und Löschen, 
                                     ggf. an dein eigenes Routing anpassen -->
                                <a href="?controller=product&action=edit&id=<?php echo urlencode($id); ?>" class="btn btn-secondary">Bearbeiten</a>
                                <a href="?controller=product&action=delete&id=<?php echo urlencode($id); ?>" class="btn btn-delete" onclick="return confirm('Wirklich löschen?');">Löschen</a>
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
