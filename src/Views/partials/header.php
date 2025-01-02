<!-- src/Views/partials/header.php -->
<?php
// Prüfe, ob eingeloggt, ob Admin
$isLoggedIn = !empty($_SESSION['user_id']);
$isAdmin    = !empty($_SESSION['is_admin']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Webshop Backend'); ?></title>
    
    <!-- Haupt-CSS (ausgelagerte Datei) -->
    <link rel="stylesheet" href="/css/main.css">

    <!-- jQuery von CDN einbinden -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js">
    </script>

    <!-- Dein eigenes Script -->
    <script>
    $(document).ready(function(){
        $('.has-submenu > a').on('click', function(e){
            e.preventDefault(); // Standard-Linkverhalten verhindern
            $(this).parent().toggleClass('open'); // Toggle Klasse 'open'
        });
    });
    </script>
  
</head>
<body>

<div class="admin-wrapper">

    <nav class="admin-sidebar">
        <div class="sidebar-header">
            <h2>Webshop Backend</h2>
        </div>

        <ul class="sidebar-nav">
            <?php if ($isLoggedIn): ?>
                
                <!-- Haupt-Menüpunkte -->
                <li><a href="/">Dashboard</a></li>

                <!-- Beispiel: Artikel mit Unterlinks -->
                <li class="has-submenu">
                    <a href="/artikel">
                        Artikel <span class="submenu-toggle">▼</span>
                    </a>
                    <ul class="sub-nav">
                        <li><a href="/artikel/neu">Neu</a></li>
                        <li><a href="/artikel/import">Import</a></li>
                    </ul>
                </li>

                <!-- Bestellungen als einfacher Menüpunkt -->
                <li><a href="/bestellungen">Bestellungen</a></li>

                <!-- Beispiel: Benutzerverwaltung (nur, wenn Admin) -->
                <?php if ($isAdmin): ?>
                    <li class="has-submenu">
                        <a href="/userlist">Benutzerverwaltung</a>
                        <ul class="sub-nav">
                            <li><a href="/userlist/neu">Benutzer anlegen</a></li>
                            <li><a href="/userlist/import">Import</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <li><a href="/logout">Logout</a></li>

            <?php else: ?>
                <!-- Nicht eingeloggt -->
                <li><a href="/login">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Hauptinhaltsbereich (rechts neben der Sidebar) -->
    <main class="admin-main">
        <!-- Hier beginnt der jeweilige Seiteninhalt -->
