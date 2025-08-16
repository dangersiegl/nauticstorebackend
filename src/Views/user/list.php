<?php
$pageTitle = 'Benutzerübersicht';
require __DIR__ . '/../partials/header.php';
?>

<div class="content-box">
    <h2>Benutzerübersicht</h2>

    <table class="admin-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>ID</th>
                <th>E-Mail</th>
                <th>Erstellt am</th>
                <th>Admin?</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        <td><?php echo !empty($user['is_admin']) ? 'Ja' : 'Nein'; ?></td>
                        <td>
                            <!-- Angepasste Button-Klassen wie in product/list.php -->
                            <a href="/user/edit/<?php echo $user['id']; ?>" class="btn btn-secondary">Bearbeiten</a>
                            <a href="/user/delete/<?php echo $user['id']; ?>" class="btn btn-delete" onclick="return confirm('Benutzer wirklich löschen?');">Löschen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 8px;">
                        Keine Benutzer gefunden.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
require __DIR__ . '/../partials/footer.php';
