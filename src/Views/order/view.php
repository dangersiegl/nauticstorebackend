<!-- src/Views/order/view.php -->

<?php
// Setze einen individuellen Seitentitel (falls gewünscht)
$pageTitle = 'Bestellung #' . $order['id'];

// Header einbinden
require __DIR__ . '/../partials/header.php'; 
?>

<h2>Bestellung #<?php echo $order['id']; ?></h2>
<p>Kunde: <?php echo htmlspecialchars($order['user_email']); ?></p>
<p>Status: <?php echo htmlspecialchars($order['status']); ?></p>
<p>Gesamtbetrag: <?php echo number_format($order['total_price'], 2); ?> €</p>
<p>Erstellt am: <?php echo $order['created_at']; ?></p>

<h3>Bestellartikel</h3>
<?php if (!empty($order['items'])): ?>
    <ul>
        <?php foreach ($order['items'] as $item): ?>
            <li>
                Artikel: <?php echo htmlspecialchars($item['name']); ?> 
                | Menge: <?php echo $item['quantity']; ?> 
                | Einzelpreis: <?php echo number_format($item['price'], 2); ?> €
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Keine Artikel vorhanden.</p>
<?php endif; ?>

<h4>Status aktualisieren</h4>
<form method="post" action="?controller=order&action=updateStatus">
    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
    <select name="status">
        <option value="pending"   <?php if ($order['status'] === 'pending')   echo 'selected'; ?>>Offen</option>
        <option value="paid"      <?php if ($order['status'] === 'paid')      echo 'selected'; ?>>Bezahlt</option>
        <option value="shipped"   <?php if ($order['status'] === 'shipped')   echo 'selected'; ?>>Versendet</option>
        <option value="cancelled" <?php if ($order['status'] === 'cancelled') echo 'selected'; ?>>Storniert</option>
    </select>
    <button type="submit">Speichern</button>
</form>

<?php 
// Footer einbinden
require __DIR__ . '/../partials/footer.php'; 
?>
