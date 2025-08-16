<?php

/**

 * public/index.php

 * Haupt-Einstiegspunkt (Front Controller)

 */



// public/index.php



ob_start(); 
session_start();

// Error Reporting
error_reporting(E_ALL); // Alle Fehler anzeigen
ini_set('display_errors', 1); // Fehlerausgabe aktivieren
//ini_set('log_errors', 1); // Fehler in Log-Datei speichern
//ini_set('error_log', '/var/log/php_errors.log'); // Log-Datei festlegen
//



// Bestimme die aktuelle Route

$route = $_GET['route'] ?? '';

$route = trim($route, '/');



// Definiere, welche Routen ohne Login erlaubt sind:

$publicRoutes = [

    'login',      // Loginformular

    'register',   // Registrierung, falls du das brauchst

    'login/totp'            // ggf. Startseite, wenn du sie als "frei zugänglich" willst

];



// Wenn der User nicht eingeloggt ist und die Route nicht in $publicRoutes ist:

if (empty($_SESSION['user_id']) && !in_array($route, $publicRoutes)) {

    // Weiterleitung zur Login-Seite

    header('Location: /login');

    exit;

}



// 2) Wichtige Dateien laden: config und Database

// Konfigurationsdatei laden (Fallback auf config_default.php)
$configFile = __DIR__ . '/../config/config.php';
if (file_exists($configFile)) {
    require_once $configFile;
} else {
    require_once __DIR__ . '/../config/config_default.php';
}

require_once __DIR__ . '/../src/Models/Database.php';

// --- Neu: Models zentral laden, damit Namespaced-Klassen (App\Models\...) verfügbar sind ---
require_once __DIR__ . '/../src/Models/UserModel.php';
require_once __DIR__ . '/../src/Models/ProductModel.php';
require_once __DIR__ . '/../src/Models/OrderModel.php';
// ...bei Bedarf weitere Models hier eintragen...



// 3) Aktuelle Route auslesen (von .htaccess => ?route=...)

$route = $_GET['route'] ?? '';

$route = trim($route, '/');  // etwa "login", "register", "product/list"



// 4) Mapping für Kurz-URLs (optional, falls du Standard-Routen abfangen willst)

$customRoutes = [

    // Leer oder "/" => Standard: home->index

    ''           => ['controller' => 'home', 'action' => 'index'],

    'dashboard'           => ['controller' => 'home', 'action' => 'index'],



    // Kurzrouten für User

    'login'      => ['controller' => 'user', 'action' => 'login'],

    'login/totp' => ['controller' => 'user', 'action' => 'loginTOTP'],

    'register'   => ['controller' => 'user', 'action' => 'register'],

    'logout'     => ['controller' => 'user', 'action' => 'logout'],

    // Kurzrouten für Bestellungen

    'bestellungen/list' => ['controller' => 'bestellungen', 'action' => 'list'],



    // Kurzrouten für Produkte

    'artikel/list'       => ['controller' => 'product', 'action' => 'list'],

    'artikel/neu'   => ['controller' => 'product', 'action' => 'create'],


    'user/neu'   => ['controller' => 'user', 'action' => 'neu'],
    'user/store' => ['controller' => 'user', 'action' => 'store'],
    'user/list' => ['controller' => 'user', 'action' => 'list'],

    'user/mfa/enable'  => ['controller' => 'user', 'action' => 'enableMFA'],
    'user/mfa/disable' => ['controller' => 'user', 'action' => 'disableMFA'],
    // Route für erneutes Senden der Bestätigungs-Mail (nutzt uid GET-Param für Admins)
    'user/resend-confirm-email' => ['controller' => 'user', 'action' => 'resendConfirmEmail'],
    // Oder falls du einen eigenen Controller MfaController hast:
    // 'mfa/enable' => ['controller' => 'mfa', 'action' => 'enable']

];



// 5) Prüfen, ob $route in den $customRoutes liegt

if (array_key_exists($route, $customRoutes)) {

    $controllerName = $customRoutes[$route]['controller'];

    $actionName     = $customRoutes[$route]['action'];

} else {

    // Kein Eintrag im $customRoutes => wir schauen,

    // ob die Route z. B. "product/list" ist (2-teilig).



    if (!$route) {

        // Wenn komplett leer, Standard-Route

        $controllerName = 'home';

        $actionName     = 'index';

    } else {

        // Aufsplitten

        $parts = explode('/', $route);

        $controllerName = $parts[0] ?? 'home';

        $actionName     = $parts[1] ?? 'index';

    }

}



// 6) Controller-Klasse und Datei bestimmen

$controllerClass = ucfirst($controllerName) . 'Controller';

$controllerFile  = __DIR__ . '/../src/Controllers/' . $controllerClass . '.php';



// 7) Prüfen, ob die Controller-Datei existiert

if (!file_exists($controllerFile)) {

    exit("Controller '$controllerClass' nicht gefunden.");

}



// 8) Laden und Instanzieren
require_once $controllerFile;
$controller = new $controllerClass;

// --- Neuer, korrigierter Dispatch-Block ---
// Teile der Route als Parameter (falls vorhanden)
$parts = $route === '' ? [] : explode('/', $route);

// Action-Bestandteil ermitteln (aus customRoutes oder aus $parts)
$actionPart = $actionName ?? ($parts[1] ?? 'index');

// Normalisiere mögliche Action-Namen (kandidaten)
$actionNormalized = str_replace(['-', ' '], '_', $actionPart);
$actionCamel = lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $actionNormalized))));
$actionCandidates = [
    $actionPart,
    $actionNormalized,
    strtolower($actionNormalized),
    $actionCamel,
    $actionCamel . 'Action',
    $actionNormalized . 'Action',
    $actionPart . 'Action'
];

// Finde die erste existierende Methode in der Controller-Instanz
$actionToCall = null;
foreach ($actionCandidates as $cand) {
    if (method_exists($controller, $cand)) {
        $actionToCall = $cand;
        break;
    }
}

if ($actionToCall === null) {
    error_log("Router: keine Action gefunden für Route '{$route}' (candidates: " . implode(',', $actionCandidates) . ")");
    header("HTTP/1.0 404 Not Found");
    exit("Aktion '{$actionPart}' im Controller '{$controllerClass}' existiert nicht.");
}

// Rufe die Aktion auf und übergebe weitere Pfadteile als Parameter
$params = array_slice($parts, 2);
call_user_func_array([$controller, $actionToCall], $params);
$controller = new $controllerName();
call_user_func_array([$controller, $actionToCall], array_slice($parts, 2));

