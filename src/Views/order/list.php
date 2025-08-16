<!-- src/Views/order/list.php -->

<?php
$pageTitle = 'Bestellungen';
require __DIR__ . '/../partials/header.php';
?>

<div class="content-box">
    <h2>Bestellungen</h2>

    <?php if (!empty($orders)): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kunde (E-Mail)</th>
                    <th>Status</th>
                    <th>Datum</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                        <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                        <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                        <td>
                            <a href="/order/view/<?php echo urlencode($order['id']); ?>" class="btn-details">Details</a> |
                            <a href="/order/edit/<?php echo urlencode($order['id']); ?>" class="btn-edit">Bearbeiten</a> |
                            <a href="/order/delete/<?php echo urlencode($order['id']); ?>" class="btn-delete" onclick="return confirm('Bestellung wirklich löschen?');">Löschen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Keine Bestellungen vorhanden.</p>
    <?php endif; ?>
</div>

<?php
require __DIR__ . '/../partials/footer.php';
?>
