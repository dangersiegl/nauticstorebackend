<!-- src/Views/order/list.php -->

<?php
// Individueller Seitentitel
$pageTitle = 'Bestellungen';

// Header einbinden
require __DIR__ . '/../partials/header.php'; 
?>

<h2>Bestellungen</h2>

<?php if (!empty($orders)): ?>
    <table border="1" cellpadding="5">
        <tr>
            <th>Bestell-ID</th>
            <th>Kunde (E-Mail)</th>
            <th>Status</th>
            <th>Datum</th>
            <th>Aktion</th>
        </tr>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?php echo $o['id']; ?></td>
                <td><?php echo htmlspecialchars($o['user_email']); ?></td>
                <td><?php echo htmlspecialchars($o['status']); ?></td>
                <td><?php echo $o['created_at']; ?></td>
                <td>
                    <!-- Falls du SEO-freundliche URLs verwendest, 
                         könntest du hier z. B. /order/view/ID nehmen -->
                    <a href="?controller=order&action=view&id=<?php echo $o['id']; ?>">Details</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Keine Bestellungen vorhanden.</p>
<?php endif; ?>

<?php
// Footer einbinden
require __DIR__ . '/../partials/footer.php'; 
?>
