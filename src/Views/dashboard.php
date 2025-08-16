<?php

// Dashboard-Ansicht

$pageTitle = 'Dashboard';

require __DIR__ . '/partials/header.php';

?>

<div class="admin-main">
    <h2>Willkommen im Admin-Dashboard</h2>

    <p>Hier findest du eine Übersicht deiner Bestellungen, Produkte und weitere Statistiken.</p>

    <!-- Beispiel für eine Übersichtstabelle -->
    <table class="admin-table">
        <thead>
            <tr>
                <th>Bestell-ID</th>
                <th>Produkt</th>
                <th>Menge</th>
                <th>Preis</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                        <td><?php echo htmlspecialchars($order['product']); ?></td>
                        <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($order['price']); ?></td>
                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">Keine Bestellungen gefunden.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php

require __DIR__ . '/partials/footer.php';

?>
