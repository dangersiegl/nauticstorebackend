<?php
$pageTitle = 'Benutzerübersicht';
require __DIR__ . '/../partials/header.php';
?>

<div class="admin-main">
    <h2>Benutzerübersicht</h2>

    <table border="1" class="table table-striped" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="padding: 8px; background-color: #f2f2f2;">ID</th>
                <th style="padding: 8px; background-color: #f2f2f2;">E-Mail</th>
                <th style="padding: 8px; background-color: #f2f2f2;">Erstellt am</th>
                <th style="padding: 8px; background-color: #f2f2f2;">Admin?</th>
                <!-- Neue Spalten -->
                <th style="padding: 8px; background-color: #f2f2f2;">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td style="padding: 8px;">
                            <?php echo htmlspecialchars($user['id']); ?>
                        </td>
                        <td style="padding: 8px;">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td style="padding: 8px;">
                            <?php echo htmlspecialchars($user['created_at']); ?>
                        </td>
                        <td style="padding: 8px;">
                            <?php echo !empty($user['is_admin']) ? 'Ja' : 'Nein'; ?>
                        </td>
                        <!-- Neue Spalte mit Edit- und Delete-Links -->
                        <td style="padding: 8px;">
                            <!-- URL-Struktur siehe Erklärung unten -->
                            <a href="/user/edit/<?php echo $user['id']; ?>">Bearbeiten</a> |
                            <a href="/user/delete/<?php echo $user['id']; ?>"
                               onclick="return confirm('Benutzer wirklich löschen?');">
                                Löschen
                            </a>
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
