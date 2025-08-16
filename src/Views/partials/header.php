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

// Ermittle aus der Session, ob Sidebar eingeklappt ist
$sidebarCollapsed = !empty($_SESSION['sidebar_collapsed']);
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

    <!-- Font Awesome (CDN) - primär via jsDelivr -->
    <link id="fa-css" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">

    <script>
    // Kleines Fallback: falls Font Awesome nicht geladen wird, nochmal anhängen
    (function() {
        function isFA() {
            try {
                var i = document.createElement('i');
                i.className = 'fa-solid fa-circle';
                i.style.position = 'absolute';
                i.style.left = '-9999px';
                document.body.appendChild(i);
                var ff = window.getComputedStyle(i).getPropertyValue('font-family') || '';
                document.body.removeChild(i);
                return /Font Awesome|FontAwesome|\"Font Awesome 6 Free\"/i.test(ff);
            } catch (e) {
                return false;
            }
        }
        document.addEventListener('DOMContentLoaded', function(){
            setTimeout(function(){
                if (!isFA()) {
                    if (!document.getElementById('fa-fallback')) {
                        var l = document.createElement('link');
                        l.id = 'fa-fallback';
                        l.rel = 'stylesheet';
                        l.href = 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css';
                        document.head.appendChild(l);
                    }
                }
            }, 120);
        });
    })();
    </script>

    <script>
    $(document).ready(function(){
        // Hamburger-Klick => Sidebar umschalten (bestehendes Verhalten)
        $('#hamburger-btn').on('click', function() {
            $('.admin-sidebar').toggleClass('open');
        });

        // Submenü-Klick => toggelt open (nur im nicht-collapsed Modus)
        $('.has-submenu > a').on('click', function(e){
            var $sidebar = $(this).closest('.admin-sidebar');
            if ($sidebar.hasClass('collapsed')) {
                // Im collapsed Zustand: entferne collapsed (macht Menü wieder breit)
                $sidebar.removeClass('collapsed');
                // Update Session: notify server dass jetzt expanded
                $.post('/ajax/sidebar_state.php', { collapsed: 0 });
                e.preventDefault();
                return;
            }
            e.preventDefault();
            $(this).parent().toggleClass('open');
        });

        // Neuer Toggle: Sidebar in "collapsed" (nur Icons) umschalten
        $('#sidebar-toggle').on('click', function(e){
            e.preventDefault();
            var $sidebar = $('.admin-sidebar');
            $sidebar.toggleClass('collapsed');

            // aktuellen Zustand an Server senden (1 = collapsed, 0 = expanded)
            var isCollapsed = $sidebar.hasClass('collapsed') ? 1 : 0;
            $.post('/ajax/sidebar_state.php', { collapsed: isCollapsed })
                .fail(function(){ /* optional: Fehlertoleranz */ });
        });

        // Hover-Popup für collapsed Sidebar: positionieren und öffnen (einfach: top = 0 relativ zum LI)
        $('.admin-sidebar').on('pointerenter', '.has-submenu', function(e){
            var $sidebar = $(this).closest('.admin-sidebar');
            if (!$sidebar.hasClass('collapsed')) return;

            var $li = $(this);
            var $sub = $li.children('.sub-nav');

            // Direkt am Listeneintrag ausrichten - top 0 (oder kleiner negativer Offset falls gewünscht)
            $sub.css({ top: '0px' });

            // Klasse für Sichtbarkeit (CSS steuert Anzeige/Transition)
            $li.addClass('hover-open');
        });

        $('.admin-sidebar').on('pointerleave', '.has-submenu', function(e){
            var $li = $(this);
            $li.removeClass('hover-open');
            // Top zurücksetzen (falls vorher gesetzt)
            $li.children('.sub-nav').css({ top: '' });
        });

        // Klick außerhalb schließt alle hover-open
        $(document).on('click', function(e){
            if (!$(e.target).closest('.admin-sidebar').length) {
                $('.admin-sidebar .has-submenu.hover-open').removeClass('hover-open');
            }
        });
    });
    </script>

    <style>
    /* Dein bestehender CSS-Code */
    </style>
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

        <nav class="admin-sidebar<?php echo $sidebarCollapsed ? ' collapsed' : ''; ?>">
            <!-- ...existing sidebar header ... -->

            <ul class="sidebar-nav">
                <!-- Toggle als regulärer Menüeintrag (Icon + Label), ganz oben -->
                <li class="sidebar-toggle-item">
                    <a href="#" id="sidebar-toggle" title="Menü ein-/ausklappen" aria-label="Menü ein-/ausklappen">
                        <i class="fa-solid fa-bars nav-icon" aria-hidden="true"></i>
                        <span class="nav-label">Menü</span>
                    </a>
                </li>
                 <?php if ($isLoggedIn): ?>
                     <li class="<?php echo ($activeController === 'dashboard' ? 'active' : ''); ?>">
                         <a href="/" title="Dashboard" aria-label="Dashboard">
                             <i class="fa-solid fa-house nav-icon" aria-hidden="true"></i>
                             <span class="nav-label">Dashboard</span>
                         </a>
                     </li>
 
                     <li class="has-submenu <?php echo ($activeController === 'artikel' ? 'open' : ''); ?>">
                         <a href="/artikel" class="<?php echo ($activeController === 'artikel' ? 'active' : ''); ?>" title="Artikel" aria-label="Artikel">
                             <i class="fa-solid fa-box-open nav-icon" aria-hidden="true"></i>
                             <span class="nav-label">Artikel</span>
                             <span class="submenu-toggle">▼</span>
                         </a>
                         <ul class="sub-nav">
                             <li class="<?php echo ($activeAction === 'list' ? 'active' : ''); ?>">
                                 <a href="/artikel/list" title="Artikel anzeigen" aria-label="Artikel anzeigen"><i class="fa-solid fa-list nav-icon" aria-hidden="true"></i><span class="nav-label">Anzeigen</span></a>
                             </li>
                             <li class="<?php echo ($activeAction === 'neu' ? 'active' : ''); ?>">
                                 <a href="/artikel/neu" title="Neues Artikel anlegen" aria-label="Neues Artikel anlegen"><i class="fa-solid fa-plus nav-icon" aria-hidden="true"></i><span class="nav-label">Neu</span></a>
                             </li>
                         </ul>
                     </li>
 
                     <li class="has-submenu <?php echo ($activeController === 'order' ? 'open' : ''); ?>">
                         <a href="/order" class="<?php echo ($activeController === 'order' ? 'active' : ''); ?>" title="Bestellungen" aria-label="Bestellungen">
                             <i class="fa-solid fa-basket-shopping nav-icon" aria-hidden="true"></i>
                             <span class="nav-label">Bestellungen</span>
                             <span class="submenu-toggle">▼</span>
                         </a>
                         <ul class="sub-nav">
                             <li class="<?php echo ($activeAction === 'list' ? 'active' : ''); ?>">
                                 <a href="/order/list" title="Bestellungen anzeigen" aria-label="Bestellungen anzeigen"><i class="fa-solid fa-list nav-icon" aria-hidden="true"></i><span class="nav-label">Anzeigen</span></a>
                             </li>
                             <li class="<?php echo ($activeAction === 'neu' ? 'active' : ''); ?>">
                                 <a href="/order/neu" title="Neue Bestellung anlegen" aria-label="Neue Bestellung anlegen"><i class="fa-solid fa-plus nav-icon" aria-hidden="true"></i><span class="nav-label">Neu</span></a>
                             </li>
                         </ul>
                     </li>
 
                     <?php if ($isAdmin): ?>
                         <li class="has-submenu <?php echo ($activeController === 'user' ? 'open' : ''); ?>">
                        <a href="/user/list" title="Kunden" aria-label="Kunden">
                            <i class="fa-solid fa-users nav-icon" aria-hidden="true"></i>
                            <span class="nav-label">Kunden</span>
                            <span class="submenu-toggle">▼</span>
                        </a>
                        <ul class="sub-nav">
                            <li class="<?php echo ($activeController === 'user' && $activeAction === 'list' ? 'active' : ''); ?>">
                                <a href="/user/list" title="Alle Kunden anzeigen" aria-label="Alle Kunden anzeigen">
                                    <i class="fa-solid fa-list nav-icon" aria-hidden="true"></i>
                                    <span class="nav-label">Anzeigen</span>
                                </a>
                            </li>
                            <li class="<?php echo ($activeController === 'user' && $activeAction === 'neu' ? 'active' : ''); ?>">
                                <a href="/user/neu" title="Neuen Kunden anlegen" aria-label="Neuen Kunden anlegen">
                                    <i class="fa-solid fa-plus nav-icon" aria-hidden="true"></i>
                                    <span class="nav-label">Neu</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- Benutzerverwaltung = eigener Account (für alle Benutzer verfügbar) -->
                    <li class="has-submenu <?php echo ($activeController === 'user' && ($activeAction === 'edit' || $activeAction === 'changePassword' || $activeAction === 'enableMFA') ? 'open' : ''); ?>">
                        <a href="/user/edit" title="Benutzerverwaltung" aria-label="Benutzerverwaltung">
                            <i class="fa-solid fa-user-gear nav-icon" aria-hidden="true"></i>
                            <span class="nav-label">Benutzerverwaltung</span>
                            <span class="submenu-toggle">▼</span>
                        </a>
                        <ul class="sub-nav">
                            <li>
                                <a href="/user/edit" title="Meine Daten" aria-label="Meine Daten">
                                    <i class="fa-solid fa-id-card nav-icon" aria-hidden="true"></i>
                                    <span class="nav-label">Meine Daten</span>
                                </a>
                            </li>
                            <li>
                                <a href="/user/edit?section=password" title="Passwort ändern" aria-label="Passwort ändern">
                                    <i class="fa-solid fa-key nav-icon" aria-hidden="true"></i>
                                    <span class="nav-label">Passwort</span>
                                </a>
                            </li>
                            <li>
                                <a href="/user/mfa/enable" title="Multifaktor-Authentifizierung" aria-label="Multifaktor-Authentifizierung">
                                    <i class="fa-solid fa-lock nav-icon" aria-hidden="true"></i>
                                    <span class="nav-label">MFA</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li>
                        <a href="/logout" title="Abmelden" aria-label="Abmelden">
                            <i class="fa-solid fa-right-from-bracket nav-icon" aria-hidden="true"></i>
                            <span class="nav-label">Logout</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="<?php echo ($currentRoute === 'login' ? 'active' : ''); ?>">
                        <a href="/login" title="Login" aria-label="Login">
                            <i class="fa-solid fa-key nav-icon" aria-hidden="true"></i>
                            <span class="nav-label">Login</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <main class="admin-main">
            <!-- Dein Hauptinhalt hier -->
             <!-- Dein Hauptinhalt hier -->
        <main class="admin-main">
            <!-- Dein Hauptinhalt hier -->
