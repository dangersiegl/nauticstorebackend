<!-- src/Views/partials/header.php -->
<?php
// Session sollte bereits in index.php gestartet worden sein
if (session_status() !== PHP_SESSION_ACTIVE) {
    exit('Session nicht gestartet. Überprüfe index.php!');
}

// Prüfe, ob eingeloggt, ob Admin.
$isLoggedIn = !empty($_SESSION['user_id']);
$isAdmin    = !empty($_SESSION['is_admin']);

// Ermittle die aktuelle Route.
$currentRoute = $_GET['route'] ?? '';
$currentRoute = trim($currentRoute, '/'); 

// Zerlege die Route.
$parts = explode('/', $currentRoute);
$activeController = $parts[0] ?? ''; // z. B. "user"
$activeAction     = $parts[1] ?? 'index'; // default = "index"
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
    <?php
    if (!empty($pageTitle)) {
        echo htmlspecialchars($pageTitle) . ' - Nauticstore24.at Backend';
    } else {
        echo 'Nauticstore24.at Backend';
    }
    ?>
    </title>

    <!-- Dein Haupt-CSS -->
    <link rel="stylesheet" href="/css/main.css">

    <!-- Favicon Einbindung -->
    <link rel="icon" href="/assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="icon" href="/assets/favicons/favicon-32x32.png" sizes="32x32" type="image/png">
    <link rel="icon" href="/assets/favicons/favicon-16x16.png" sizes="16x16" type="image/png">
    <link rel="manifest" href="/assets/favicons/manifest.json">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicons/apple-touch-icon.png">
    <meta name="theme-color" content="#1f2d3b">

    <!-- jQuery von CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- CKEditor 5 CDN -->
    <script src="https://cdn.ckeditor.com/ckeditor5/35.0.1/classic/ckeditor.js"></script>

    <script>
    $(document).ready(function(){
        // Hamburger-Klick => Sidebar umschalten
        $('#hamburger-btn').on('click', function() {
            $('.admin-sidebar').toggleClass('open');
        });

        // Submenü-Klick => toggelt open
        $('.has-submenu > a').on('click', function(e){
            e.preventDefault();
            $(this).parent().toggleClass('open');
        });
    });
    </script>
</head>
<body>
    <header id="main-header">
        <div class="header-content">
            <div class="logo">
                <a href="/">
                    <img src="/assets/logo/nauticstore24logo-backend.png" alt="Nauticstore24 - Backend">
                    <!-- Andernfalls kannst du den Website-Namen als Text anzeigen -->
                </a>
            </div>
        </div>
        <!-- Hamburger-Button rechts -->
        <div id="hamburger-btn">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </header>

    <div class="admin-wrapper">

        <nav class="admin-sidebar">
            <ul class="sidebar-nav">
                <?php if ($isLoggedIn): ?>
                    <li class="<?php echo ($activeController === 'dashboard' ? 'active' : ''); ?>">
                        <a href="/">Dashboard</a>
                    </li>
                    <li class="has-submenu <?php echo ($activeController === 'artikel' ? 'open' : ''); ?>">
                        <a href="/artikel" class="<?php echo ($activeController === 'artikel' ? 'active' : ''); ?>">
                            Artikel <span class="submenu-toggle">▼</span>
                        </a>
                        <ul class="sub-nav">
                            <li class="<?php echo ($activeAction === 'list' ? 'active' : ''); ?>">
                                <a href="/artikel/list">Anzeigen</a>
                            </li>
                            <li class="<?php echo ($activeAction === 'neu' ? 'active' : ''); ?>">
                                <a href="/artikel/neu">Neu</a>
                            </li>
                        </ul>
                    </li>
                    <li class="has-submenu <?php echo ($activeController === 'order' ? 'open' : ''); ?>">
                        <a href="/order" class="<?php echo ($activeController === 'order' ? 'active' : ''); ?>">
                            Bestellungen <span class="submenu-toggle">▼</span>
                        </a>
                        <ul class="sub-nav">
                            <li class="<?php echo ($activeAction === 'list' ? 'active' : ''); ?>">
                                <a href="/order/list">Anzeigen</a>
                            </li>
                            <li class="<?php echo ($activeAction === 'neu' ? 'active' : ''); ?>">
                                <a href="/order/neu">Neu</a>
                            </li>
                        </ul>
                    </li>
                    <?php if ($isAdmin): ?>
                        <li class="has-submenu <?php echo ($activeController === 'user' ? 'open' : ''); ?>">
                            <a href="/user" class="<?php echo ($activeController === 'user' ? 'active' : ''); ?>">
                                Benutzerverwaltung <span class="submenu-toggle">▼</span>
                            </a>
                            <ul class="sub-nav">
                                <li class="<?php echo ($activeAction === 'list' ? 'active' : ''); ?>">
                                    <a href="/user/list">Anzeigen</a>
                                </li>
                                <li class="<?php echo ($activeAction === 'mfa' ? 'active' : ''); ?>">
                                    <a href="/user/mfa/enable">MFA</a>
                                </li>
                                <!-- Weitere User-Routen hier -->
                            </ul>
                        </li>
                    <?php endif; ?>
                    <li><a href="/logout">Logout</a></li>
                <?php else: ?>
                    <li class="<?php echo ($currentRoute === 'login' ? 'active' : ''); ?>">
                        <a href="/login">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <main class="admin-main">
            <!-- Dein Hauptinhalt hier -->
