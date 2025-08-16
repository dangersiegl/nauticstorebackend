<?php

/**

 * public/index.php

 * Haupt-Einstiegspunkt (Front Controller)

 */



// public/index.php



session_start();



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

    'user/mfa/enable' => ['controller' => 'user', 'action' => 'enableMFA'],
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

$controller = new $controllerClass();



// 9) Prüfen, ob die gewünschte Methode existiert

if (!method_exists($controller, $actionName)) {

    exit("Aktion '$actionName' im Controller '$controllerClass' existiert nicht.");

}



// 10) Aktion aufrufen

$controller->$actionName();

