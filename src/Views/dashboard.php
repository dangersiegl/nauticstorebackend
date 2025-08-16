<?php

// Dashboard-Ansicht

$pageTitle = 'Dashboard';

require __DIR__ . '/partials/header.php';

?>

<div class="content-box">
    <h2>Dashboard</h2>

    <p>Willkommen im Backend. Hier finden Sie eine Übersicht über den aktuellen Systemstatus.</p>

    <!-- Platzhalter-Karten / Kennzahlen (optional, mit Daten befüllen wenn vorhanden) -->
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;">
        <div style="flex:1 1 200px;padding:12px;border-radius:6px;background:#f9fafb;border:1px solid #ececec;">
            <h3 style="margin:0 0 8px 0;">Benutzer</h3>
            <p style="margin:0;font-size:1.25rem;">&mdash;</p>
        </div>
        <div style="flex:1 1 200px;padding:12px;border-radius:6px;background:#f9fafb;border:1px solid #ececec;">
            <h3 style="margin:0 0 8px 0;">Produkte</h3>
            <p style="margin:0;font-size:1.25rem;">&mdash;</p>
        </div>
        <div style="flex:1 1 200px;padding:12px;border-radius:6px;background:#f9fafb;border:1px solid #ececec;">
            <h3 style="margin:0 0 8px 0;">Bestellungen</h3>
            <p style="margin:0;font-size:1.25rem;">&mdash;</p>
        </div>
    </div>

    <!-- Weitere Dashboard-Inhalte hier einfügen -->
</div>

<?php

require __DIR__ . '/partials/footer.php';

?>
