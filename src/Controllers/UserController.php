<?php
/**
 * src/Controllers/UserController.php
 */

require_once __DIR__ . '/../Models/UserModel.php';

class UserController
{
    /**
     * Registrierungsformular anzeigen + Logik
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Passwort hashen
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // TOTP-Secret generieren
            $totpSecret = $this->generateTOTPSecret();

            // Benutzer in der Datenbank speichern
            $db = new Database();
            $success = $db->query(
                "INSERT INTO users (email, password, totp_secret) VALUES (?, ?, ?)",
                [$email, $hashedPassword, $totpSecret]
            );

            if ($success) {
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
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['email']) && isset($_POST['password'])) {
                // Schritt 1: Email und Passwort überprüfen
                $email = $_POST['email'];
                $password = $_POST['password'];

                $db = new Database();
                $user = $db->query("SELECT * FROM users WHERE email = ?", [$email])->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if (!empty($user['totp_secret'])) {
                        // Benutzer hat TOTP aktiviert -> Weiter zu Schritt 2
                        $_SESSION['pending_user_id'] = $user['id'];
                        header('Location: /login/totp');
                        exit;
                    } else {
                        // Benutzer hat kein TOTP -> Direkt einloggen
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['is_admin'] = $user['is_admin'];
                        header('Location: /dashboard');
                        exit;
                    }
                } else {
                    $error = "Ungültige Login-Daten!";
                }
            } elseif (isset($_POST['totp'])) {
                // Schritt 2: TOTP-Code überprüfen
                if (isset($_SESSION['pending_user_id'])) {
                    $userId = $_SESSION['pending_user_id'];
                    $totpCode = $_POST['totp'];

                    $db = new Database();
                    $user = $db->query("SELECT * FROM users WHERE id = ?", [$userId])->fetch();

                    if ($user && $this->verifyTOTP($user['totp_secret'], $totpCode)) {
                        // TOTP korrekt -> Benutzer einloggen
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['is_admin'] = $user['is_admin'];
                        unset($_SESSION['pending_user_id']);
                        header('Location: /dashboard');
                        exit;
                    } else {
                        $error = "Ungültiger TOTP-Code!";
                    }
                } else {
                    $error = "Sitzung abgelaufen. Bitte erneut einloggen.";
                    header('Location: /login');
                    exit;
                }
            }
        }

        // Unterscheide, ob wir auf der ersten oder zweiten Seite sind
        if (isset($_GET['action']) && $_GET['action'] === 'totp') {
            require_once __DIR__ . '/../Views/user/login_totp.php';
        } else {
            require_once __DIR__ . '/../Views/user/login.php';
        }
    }

    /**
     * Meldet den aktuellen Benutzer ab und zerstört die Session
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Alle Session-Daten entfernen
        $_SESSION = [];

        // Session-Cookie löschen, falls vorhanden
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();

        header('Location: /login');
        exit;
    }

    /**
     * TOTP-Secret generieren
     */
    private function generateTOTPSecret() {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $secret;
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
        $buffer = '';
        $bitsLeft = 0;
        $result = '';

        foreach (str_split($data) as $char) {
            $buffer = ($buffer << 5) | strpos($alphabet, $char);
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
    private function generateTOTPProvisioningUri($email, $secret) {
        $issuer = 'Webshop Backend'; // Dein Systemname
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $email) .
               '?secret=' . $secret . '&issuer=' . rawurlencode($issuer);
    }
}
