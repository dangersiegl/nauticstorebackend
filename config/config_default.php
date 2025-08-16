<?php
/**
 * config/config.php
 */

// DB-Konfiguration (bitte anpassen)
define('DB_HOST', 'localhost');
define('DB_NAME', 'd042e965');
define('DB_USER', 'd042e965');
define('DB_PASS', 'hUbLTgbPNe2jURM9bxY9');

// Weitere Einstellungen
define('APP_NAME', 'Nauticstore24.at');
define('BASE_URL', 'https://dngr.at/os/nauticstore'); // Anpassen, wenn SSL aktiv oder anderer Domainname

// SMTP / Mail-Einstellungen (anpassen)
define('SMTP_HOST', 'smtp.world4you.com');            // z.B. 'smtp.example.com'
define('SMTP_PORT', 587);          // z.B. 587 oder 465
define('SMTP_USER', 'nauticstore24@dngr.at');           // SMTP-Benutzername
define('SMTP_PASS', '!AU5d9s6f411..');           // SMTP-Passwort
define('SMTP_SECURE', 'tls');      // 'tls' oder 'ssl' oder '' für none
define('SMTP_AUTH', true);         // true falls Auth erforderlich
define('SMTP_FROM', 'nauticstore24@dngr.at'); // Default From-Adresse
define('SMTP_FROM_NAME', 'Nauticstore24');         // Default From-Name
