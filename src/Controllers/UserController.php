<?php

/**

 * src/Controllers/UserController.php

 */

use App\Models\UserModel;



class UserController

{

    /**

     * Registrierungsformular anzeigen + Logik

     */

    public function register() {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $email = $_POST['email'] ?? '';

            $password = $_POST['password'] ?? '';





            // TOTP-Secret generieren

            $totpSecret = $this->generateTOTPSecret();



            // Benutzer in der Datenbank speichern
            $created = UserModel::register($email, $password, $totpSecret);

            if ($created) {
                // QR-Code anzeigen
                $uri = $this->generateTOTPProvisioningUri($email, $totpSecret);
                echo "Bitte scanne diesen QR-Code mit Google Authenticator:<br>";
                echo "<img src='https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=" . urlencode($uri) . "' alt='QR Code'>";
            } else {
                echo "Registrierung fehlgeschlagen!";
            }


        } else {

            require_once __DIR__ . '/../Views/user/register.php';

        }

    }



    /**

     * Login in zwei Schritten

     */


    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Schritt 1: Login mit E-Mail und Passwort
            if (isset($_POST['email']) && isset($_POST['password'])) {
                $email = $_POST['email'];
                $password = $_POST['password'];

                $user = UserModel::verifyLogin($email, $password);

                if ($user) {
                    if (!empty($user['totp_secret'])) {
                        // Weiterleitung zur TOTP-Seite, falls ein Secret existiert
                        $_SESSION['pending_user_id'] = $user['id'];
                        header('Location: /login/totp');
                        exit;
                    } else {
                        // Direkt einloggen
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['is_admin'] = $user['is_admin'];
                        header('Location: /dashboard');
                        exit;
                    }
                } else {
                    $error = 'Ungültige Login-Daten!';
                }
            }
        }

        // Standard: Login-Formular anzeigen
        require_once __DIR__ . '/../Views/user/login.php';
    }


    public function login_old()
    {
        // Prüfen, ob POST-Request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // 1) Wurde E-Mail + Passwort gesendet (Schritt 1)?
            if (isset($_POST['email']) && isset($_POST['password'])) {

                // E-Mail/Passwort abholen
                $email = $_POST['email'];
                $password = $_POST['password'];

                $user = UserModel::verifyLogin($email, $password);

                if ($user) {
                    // Passwort korrekt
                    if (!empty($user['totp_secret'])) {
                        // Schritt 2 (TOTP) erforderlich
                        $_SESSION['pending_user_id'] = $user['id'];
                        header('Location: /login/totp');
                        exit;
                    } else {
                        // Direkt einloggen
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['is_admin'] = $user['is_admin'];
                        header('Location: /dashboard');
                        exit;
                    }
                  } else {
                      // E-Mail/Passwort falsch
                      $error = "Falsche Login-Daten!";
                  }

            // 2) Wurde ein TOTP-Code gesendet (Schritt 2)?
            } elseif (isset($_POST['totp'])) {

                if (isset($_SESSION['pending_user_id'])) {
                    $userId = $_SESSION['pending_user_id'];
                    $totpCode = $_POST['totp'];

                    $user = UserModel::getById($userId);

                    if ($user && $this->verifyTOTP($user['totp_secret'], $totpCode)) {
                        // TOTP korrekt => endgültig einloggen
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['is_admin'] = $user['is_admin'];
                        unset($_SESSION['pending_user_id']);
                        header('Location: /dashboard');
                        exit;
                    } else {
                        $error = "Ungültiger TOTP-Code!";
                    }

                } else {
                    // Session nicht mehr vorhanden
                    $error = "Sitzung abgelaufen. Bitte erneut einloggen.";
                    header('Location: /login');
                    exit;
                }

            }
            // Falls POST, aber weder (email/password) noch (totp) => kein gültiger Pfad?
            // => $error könnte man hier noch setzen, z.B. $error = "Unbekannte Anfrage";

        } // Ende if POST

        // GET-Request oder Fehlerfall => Bestimmen, welche View geladen wird
        // Falls URL: /login/totp => TOTP-Form laden
        if (isset($_GET['action']) && $_GET['action'] === 'totp') {
            require_once __DIR__ . '/../Views/user/login_totp.php';
        } else {
            // Standard: normales Login-Formular
            require_once __DIR__ . '/../Views/user/login.php';
        }
    }


    public function loginTOTP()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $totpCode = $_POST['totp'] ?? '';
            $userId   = $_SESSION['pending_user_id'] ?? null;

            if (!$userId) {
                // Falls Session abgelaufen
                $error = "Sitzung abgelaufen. Bitte erneut einloggen.";
                header('Location: /login');
                exit;
            }

            $user = UserModel::getById($userId);

            if ($user && $this->verifyTOTP($user['totp_secret'], $totpCode)) {
                // Erfolgreich: Endgültig einloggen
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_admin'] = $user['is_admin'];
                unset($_SESSION['pending_user_id']);
                header('Location: /dashboard');
                exit;
            } else {
                $error = "Ungültiger TOTP-Code!";
            }
        }

        // TOTP-Formular anzeigen
        require_once __DIR__ . '/../Views/user/login_totp.php';
    }


    public function loginTOTP_old()
    {
        // Prüfen, ob POST-Anfrage
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $totpCode = $_POST['totp'] ?? '';
            $userId   = $_SESSION['pending_user_id'] ?? null;

            if (!$userId) {
                $error = "Sitzung abgelaufen. Bitte erneut einloggen.";
                header('Location: /login');
                exit;
            }

            $user = UserModel::getById($userId);

            if (!$user) {
                $error = "Benutzer nicht gefunden.";
                require_once __DIR__ . '/../Views/user/login_totp.php';
                return;
            }

            // TOTP-Validierung
            if ($this->verifyTOTP($user['totp_secret'], $totpCode)) {
                // Erfolgreich
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_admin'] = $user['is_admin'];
                unset($_SESSION['pending_user_id']);
                header('Location: /dashboard');
                exit;
            } else {
                // Fehlgeschlagen
                $error = "Ungültiger TOTP-Code.";
                require_once __DIR__ . '/../Views/user/login_totp.php';
            }
        } else {
            // GET => TOTP-Formular anzeigen
            require_once __DIR__ . '/../Views/user/login_totp.php';
        }
    }


    public function logout()
    {
        // Session löschen bzw. beenden:
        session_destroy();

        // Ggf. Session-Variablen leeren (falls nötig):
        // $_SESSION = [];

        // Weiterleitung auf die Login-Seite:
        header('Location: /login');
        exit();
    }


    /**

     * TOTP-Secret generieren

     */

     public function enableMFA()
     {
         // Prüfen, ob eingeloggt
         if (empty($_SESSION['user_id'])) {
             header('Location: /login');
             exit;
         }
     
         // 1) TOTP-Secret generieren
         $secret = $this->generateTOTPSecret(); 
     
           // 2) In DB speichern
           UserModel::updateTotpSecret($_SESSION['user_id'], $secret);
     
         // 3) Provisioning-URI erzeugen
         //    (otpauth://totp/Issuer:Email?secret=ABC...&issuer=Issuer)
         $userEmail = $_SESSION['user_email'] ?? 'user@example.com'; // z. B. in der Session oder DB
         $issuer    = 'Nauticstore24.at'; 
         $totpUri   = $this->generateTOTPProvisioningUri($userEmail, $secret, $issuer);
     
         // 4) View aufrufen und QR-Code anzeigen
         require __DIR__ . '/../Views/mfa/enable.php';
     }
     
     // Beispiel-Hilfsfunktionen (du kannst sie auch schon haben):
     private function generateTOTPSecret($length = 16)
     {
         $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
         $secret = '';
         for ($i = 0; $i < $length; $i++) {
             $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
         }
         return $secret;
     }
     
    private function generateTOTPProvisioningUri($email, $secret, $issuer = 'Nauticstore24.at')
    {
        $issuerEncoded = urlencode($issuer);
        $emailEncoded  = urlencode($email);
        return "otpauth://totp/{$issuerEncoded}:{$emailEncoded}?secret={$secret}&issuer={$issuerEncoded}";
    }
     



    /**

     * TOTP-Code prüfen

     */

    private function verifyTOTP($secret, $inputCode, $interval = 30, $tolerance = 1) {

        $time = floor(time() / $interval);

        $secret = $this->base32Decode($secret);



        for ($i = -$tolerance; $i <= $tolerance; $i++) {

            $hash = hash_hmac('sha1', pack('N*', 0) . pack('N*', $time + $i), $secret, true);

            $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

            $code = (ord($hash[$offset]) & 0x7F) << 24 |

                    (ord($hash[$offset + 1]) & 0xFF) << 16 |

                    (ord($hash[$offset + 2]) & 0xFF) << 8 |

                    (ord($hash[$offset + 3]) & 0xFF);

            $code %= 10 ** 6;



            if (str_pad($code, 6, '0', STR_PAD_LEFT) === $inputCode) {

                return true;

            }

        }

        return false;

    }



    /**

     * Base32-Decodierung

     */

    private function base32Decode($data) {

        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

        $data = strtoupper($data);

        $buffer = 0;

        $bitsLeft = 0;

        $result = '';



        foreach (str_split($data) as $char) {
            $val = strpos($alphabet, $char);
            if ($val === false) {
                continue; // ignoriere ungültige Zeichen
            }

            $buffer = ($buffer << 5) | $val;

            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $result .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
                $bitsLeft -= 8;
            }
        }

        return $result;

    }



    /**

     * Generiert die URI für den QR-Code

     */

    public function neu()
    {
        // Formular anzeigen
        require_once __DIR__ . '/../Views/user/neu.php';
    }

    public function store()
    {
        // POST-Daten holen
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // 1) Grundlegende Validierung
        if (empty($email) || empty($password)) {
            $error = "Bitte fülle alle Felder aus!";
            require __DIR__ . '/../Views/user/neu.php';
            return; // Brich ab, damit kein weiterer Code ausgeführt wird
        }

        // 2) Prüfen, ob ein Benutzer mit dieser E-Mail existiert
        $existingUser = UserModel::getByEmail($email);

        if ($existingUser) {
            // User existiert bereits
            $error = "Ein Benutzer mit dieser E-Mail existiert bereits!";
            require __DIR__ . '/../Views/user/neu.php';
            return;
        }

        // 3) Benutzer anlegen
        if (UserModel::register($email, $password)) {
            // Weiterleitung, z.B. auf eine Userliste oder das Dashboard
            header('Location: /user/list');
            exit;
        } else {
            // Falls Insert fehlgeschlagen ist (z. B. DB-Fehler)
            $error = "Fehler beim Anlegen des Benutzers. Bitte erneut versuchen.";
            require __DIR__ . '/../Views/user/neu.php';
            return;
        }
    }

    public function list()
    {
        // 1) Alle User holen (oder gefiltert, falls du nur bestimmte willst)
        $users = UserModel::getAll();
    
        // 3) View anzeigen
        require __DIR__ . '/../Views/user/list.php';
    }
    
    public function edit()
    {
        // 1) URL auswerten
        $parts = explode('/', $_GET['route'] ?? '');
        $userId = $parts[2] ?? 0;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // (A) POST => Änderungen speichern
            $email = $_POST['email'] ?? '';
            $isAdmin = !empty($_POST['is_admin']) ? 1 : 0;

            // Kurze Validierung ...
            if (empty($email)) {
                $error = "E-Mail darf nicht leer sein!";
                // Alte Daten neu laden, um das Formular anzuzeigen
                $user = UserModel::getById($userId);
                require __DIR__ . '/../Views/user/edit.php';
                return;
            }

            // Update in DB
            UserModel::update($userId, $email, $isAdmin);

            // Zurück zur Liste
            header('Location: /user/list');
            exit;

        } else {
            // (B) GET => Formular anzeigen mit bestehenden Daten
            $user = UserModel::getById($userId);

            if (!$user) {
                // Kein User mit dieser ID
                $error = "User nicht gefunden!";
            }

            // Zeige View (edit.php) an
            require __DIR__ . '/../Views/user/edit.php';
        }
    }

    public function delete()
    {
        $parts = explode('/', $_GET['route'] ?? '');
        $userId = $parts[2] ?? 0;

        UserModel::delete($userId);

        // Zurück zur Liste
        header('Location: /user/list');
        exit;
    }


}

