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
        // Stelle sicher, dass Session läuft
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Schritt 2: TOTP-Code wurde eingegeben -> endgültige Anlage versuchen
            if (isset($_POST['totp'])) {
                $totpCode = trim($_POST['totp'] ?? '');

                // Prüfe, ob es eine temporäre Registrierung in der Session gibt
                if (empty($_SESSION['pending_reg'])) {
                    $error = "Sitzung abgelaufen. Bitte registrieren Sie sich erneut.";
                    require_once __DIR__ . '/../Views/user/register.php';
                    return;
                }

                $pending = $_SESSION['pending_reg'];
                $email = $pending['email'];
                $password = $pending['password'];
                $totpSecret = $pending['secret'];

                // TOTP validieren
                if ($this->verifyTOTP($totpSecret, $totpCode)) {
                    // TOTP korrekt -> Benutzer jetzt endgültig anlegen
                    $created = UserModel::register($email, $password, $totpSecret);

                    // Temporäre Daten entfernen
                    unset($_SESSION['pending_reg']);

                    if ($created) {
                        // User erfolgreich angelegt -> zur Login-Seite leiten
                        header('Location: /login');
                        exit;
                    } else {
                        $error = "Fehler beim Anlegen des Benutzers. Bitte versuchen Sie es erneut.";
                        require_once __DIR__ . '/../Views/user/register.php';
                        return;
                    }
                } else {
                    // TOTP falsch -> Fehlermeldung und QR-View erneut zeigen
                    $error = "Ungültiger TOTP-Code. Bitte geben Sie den aktuellen Code aus Ihrer Authenticator-App ein.";

                    // QR-Code wieder erzeugen
                    $uri = $this->generateTOTPProvisioningUri($email, $totpSecret);
                    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($uri);
                    $data = ['secret' => $totpSecret];

                    require_once __DIR__ . '/../Views/user/register_totp.php';
                    return;
                }

            // Schritt 1: E-Mail + Passwort wurden gesendet -> TOTP vorbereiten und QR anzeigen
            } elseif (isset($_POST['email']) && isset($_POST['password'])) {
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';

                // Grundlegende Validierung
                if (empty($email) || empty($password)) {
                    $error = "Bitte geben Sie E-Mail und Passwort an.";
                    require_once __DIR__ . '/../Views/user/register.php';
                    return;
                }

                // Prüfen, ob User bereits existiert
                $existing = UserModel::getByEmail($email);
                if ($existing) {
                    $error = "Ein Benutzer mit dieser E-Mail existiert bereits.";
                    require_once __DIR__ . '/../Views/user/register.php';
                    return;
                }

                // TOTP-Secret erzeugen und temporär in Session speichern (erst nach TOTP prüfen endgültig anlegen)
                $totpSecret = $this->generateTOTPSecret();
                $_SESSION['pending_reg'] = [
                    'email' => $email,
                    'password' => $password, // Achtung: Klartext nur temporär in Session
                    'secret' => $totpSecret,
                    'created_at' => time()
                ];

                // Provisioning URI und QR-URL erzeugen
                $uri = $this->generateTOTPProvisioningUri($email, $totpSecret);
                $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($uri);
                $data = ['secret' => $totpSecret];

                // Zeige TOTP-Registrierungsseite (mit Formular zur Code-Eingabe)
                require_once __DIR__ . '/../Views/user/register_totp.php';
                return;
            }

            // Falls POST, aber kein erwartetes Feld -> Formular erneut zeigen
            require_once __DIR__ . '/../Views/user/register.php';
            return;
        }

        // GET -> Registrierungsformular anzeigen
        require_once __DIR__ . '/../Views/user/register.php';
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
                    // Nur Admins dürfen sich im Backend einloggen
                    if (empty($user['is_admin']) || $user['is_admin'] != 1) {
                        $error = "Nur Administratoren dürfen sich im Backend anmelden.";
                    } else {
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
                    // Nur Admins dürfen sich im Backend einloggen
                    if (empty($user['is_admin']) || $user['is_admin'] != 1) {
                        $error = "Nur Administratoren dürfen sich im Backend anmelden.";
                    } else {
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
                        // Prüfe Admin-Recht bevor endgültig einloggen
                        if (empty($user['is_admin']) || $user['is_admin'] != 1) {
                            $error = "Nur Administratoren dürfen sich im Backend anmelden.";
                            unset($_SESSION['pending_user_id']);
                        } else {
                            // TOTP korrekt => endgültig einloggen
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['is_admin'] = $user['is_admin'];
                            unset($_SESSION['pending_user_id']);
                            header('Location: /dashboard');
                            exit;
                        }
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
                $error = "Sitzung abgelaufen. Bitte loggen Sie sich erneut ein.";
                header('Location: /login');
                exit;
            }

            $user = UserModel::getById($userId);

            if ($user && $this->verifyTOTP($user['totp_secret'], $totpCode)) {
                // Prüfe Admin-Recht bevor endgültig einloggen
                if (empty($user['is_admin']) || $user['is_admin'] != 1) {
                    $error = "Nur Administratoren dürfen sich im Backend anmelden.";
                    unset($_SESSION['pending_user_id']);
                    require_once __DIR__ . '/../Views/user/login.php';
                    return;
                }

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
                $error = "Sitzung abgelaufen. Bitte loggen Sie sich erneut ein.";
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
                // Prüfe Admin-Recht bevor endgültig einloggen
                if (empty($user['is_admin']) || $user['is_admin'] != 1) {
                    $error = "Nur Administratoren dürfen sich im Backend anmelden.";
                    unset($_SESSION['pending_user_id']);
                    require_once __DIR__ . '/../Views/user/login.php';
                    return;
                }

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
         // Session sicherstellen
         if (session_status() === PHP_SESSION_NONE) {
             session_start();
         }

         // Login sicherstellen
         if (empty($_SESSION['user_id'])) {
             header('Location: /login');
             exit;
         }

         $userId = $_SESSION['user_id'];
         $user = UserModel::getById($userId);

         if (!$user) {
             die('Benutzer nicht gefunden.');
         }

         // Wenn bereits aktiviert
         if (!empty($user['totp_secret'])) {
             $mfa_enabled = true;
             // View zeigt "aktiviert" + Deaktivieren-Formular
             require_once __DIR__ . '/../Views/mfa/enable.php';
             return;
         }

         // POST: TOTP-Code zur Bestätigung eingegangen -> endgültig aktivieren
         if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['totp'])) {
             $code = trim($_POST['totp'] ?? '');
             $pending = $_SESSION['pending_mfa'] ?? null;

            if (empty($pending) || empty($pending['secret'])) {
                $error = "Sitzung abgelaufen. Bitte starten Sie die Aktivierung erneut.";
                require_once __DIR__ . '/../Views/mfa/enable.php';
                return;
            }

            $secret = $pending['secret'];
            if ($this->verifyTOTP($secret, $code)) {
                // Speichern des Secrets in der DB
                $saved = UserModel::setTOTPSecret($userId, $secret);
                unset($_SESSION['pending_mfa']);

                if ($saved) {
                    // Erfolgreich aktiviert
                    header('Location: /user/mfa/enable');
                    exit;
                } else {
                    $error = "Fehler beim Aktivieren. Bitte versuchen Sie es erneut.";
                    require_once __DIR__ . '/../Views/mfa/enable.php';
                    return;
                }
            } else {
                // Falscher Code -> QR/Secret wieder anzeigen
                $error = "Ungültiger TOTP-Code. Bitte geben Sie den aktuellen Code aus Ihrer Authenticator-App ein.";
                $totpUri = $this->generateTOTPProvisioningUri($user['email'] ?? $userId, $secret);
                $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($totpUri);
                $secretToShow = $secret;
                require_once __DIR__ . '/../Views/mfa/enable.php';
                return;
            }
         }

         // GET: vorbereiten (temporäres Secret in Session speichern und QR zeigen)
         $totpSecret = $this->generateTOTPSecret();
         $_SESSION['pending_mfa'] = ['secret' => $totpSecret, 'created_at' => time()];

         // Provisioning URI (Label: Email falls vorhanden)
         $totpUri = $this->generateTOTPProvisioningUri($user['email'] ?? $userId, $totpSecret);
         $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($totpUri);
         $secretToShow = $totpSecret;
         // View zeigt QR + Eingabeformular für Bestätigungscode
         require_once __DIR__ . '/../Views/mfa/enable.php';
     }

     /**

     * TOTP-Code prüfen

     */




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

        // Wenn keine ID über URL, verwende eingeloggten User (eigener Account)
        if (empty($userId)) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userId = $_SESSION['user_id'] ?? 0;
        }

        // Falls weiterhin keine ID bestimmbar, Fehler
        if (empty($userId)) {
            $error = "Benutzer nicht gefunden.";
            require __DIR__ . '/../Views/user/edit.php';
            return;
        }

        // Aktuellen Benutzerdaten laden
        $user = UserModel::getById($userId);

        if (!$user) {
            $error = "User nicht gefunden!";
            require __DIR__ . '/../Views/user/edit.php';
            return;
        }

        // POST-Verarbeitung
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // 1) Passwort-Änderung (wenn password-formular gesendet wurde)
            if (isset($_POST['new_password'])) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $currentUserId = $_SESSION['user_id'] ?? 0;

                // Optional: wenn verlangt, prüfe aktuelles Passwort (wenn Feld vorhanden)
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';

                if (empty($newPassword) || $newPassword !== $newPasswordConfirm) {
                    $error = "Neue Passwörter stimmen nicht überein oder sind leer.";
                    require __DIR__ . '/../Views/user/edit.php';
                    return;
                }

                // Optional: prüfen, ob current password korrekt (nur für own account)
                if ($currentUserId === $userId && !empty($currentPassword)) {
                    if (!UserModel::verifyPassword($userId, $currentPassword)) {
                        $error = "Aktuelles Passwort falsch.";
                        require __DIR__ . '/../Views/user/edit.php';
                        return;
                    }
                }

                // Speichere neues Passwort (UserModel::updatePassword sollte existieren)
                $changed = UserModel::updatePassword($userId, $newPassword);
                if ($changed) {
                    $success = "Passwort wurde erfolgreich geändert.";
                } else {
                    $error = "Fehler beim Ändern des Passworts.";
                }

                // Lade View erneut
                require __DIR__ . '/../Views/user/edit.php';
                return;
            }

            // 2) Allgemeine Profildaten ändern (E-Mail, ggf. is_admin)
            $email = $_POST['email'] ?? '';
            // Nur erlauben, is_admin zu setzen, wenn aktueller User Admin und er nicht sein eigenes is_admin ändert
            $isAdminFlag = 0;
            if (session_status() === PHP_SESSION_NONE) session_start();
            $currentUserIsAdmin = !empty($_SESSION['is_admin']);
            $currentUserId = $_SESSION['user_id'] ?? 0;

            if ($currentUserIsAdmin && ($currentUserId != $userId)) {
                $isAdminFlag = !empty($_POST['is_admin']) ? 1 : 0;
            } else {
                // Behalte bestehenden is_admin-Wert
                $isAdminFlag = !empty($user['is_admin']) ? 1 : 0;
            }

            // Kurze Validierung
            if (empty($email)) {
                $error = "E-Mail darf nicht leer sein!";
                require __DIR__ . '/../Views/user/edit.php';
                return;
            }

            // Update in DB
            UserModel::update($userId, $email, $isAdminFlag);

            // Bei eigenem Profil ggf. Session-Email aktualisieren (falls benötigt)
            if ($currentUserId === $userId) {
                $_SESSION['user_email'] = $email;
            }

            // Zurück zur Liste oder Profilseite
            if ($currentUserIsAdmin && ($currentUserId != $userId)) {
                header('Location: /user/list');
                exit;
            } else {
                $success = "Daten gespeichert.";
                // neu laden, damit aktuelle Werte angezeigt werden
                $user = UserModel::getById($userId);
                require __DIR__ . '/../Views/user/edit.php';
                return;
            }
        }

        // GET => Formular anzeigen mit bestehenden Daten
        require __DIR__ . '/../Views/user/edit.php';
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


    public function disableMFA()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Nur POST akzeptieren
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /user/mfa/enable');
            exit;
        }

        // Login sicherstellen
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $password = $_POST['password'] ?? '';

        if (empty($password)) {
            // Fehlermeldung zurück zur View
            $error = "Bitte geben Sie Ihr Passwort zur Bestätigung ein.";
            $user = UserModel::getById($userId);
            $mfa_enabled = !empty($user['totp_secret']);
            require_once __DIR__ . '/../Views/mfa/enable.php';
            return;
        }

        // Passwort prüfen
        if (!UserModel::verifyPassword($userId, $password)) {
            $error = "Passwort falsch. MFA wurde nicht deaktiviert.";
            $user = UserModel::getById($userId);
            $mfa_enabled = !empty($user['totp_secret']);
            require_once __DIR__ . '/../Views/mfa/enable.php';
            return;
        }

        // Passwort korrekt -> MFA deaktivieren
        $disabled = UserModel::disableMFA($userId);
        if ($disabled) {
            $_SESSION['success_message'] = 'Zwei-Faktor-Authentifizierung wurde deaktiviert.';
        } else {
            $_SESSION['error_message'] = 'Fehler beim Deaktivieren der Zwei-Faktor-Authentifizierung.';
        }

        header('Location: /user/mfa/enable');
        exit;
    }

	// --- TOTP / MFA Hilfsmethoden (einmalig, keine Duplikate) ---
	// Generiert ein neues TOTP-Secret (Base32)
	protected function generateTOTPSecret(): string
	{
		$random = random_bytes(20); // 160 bit
		return $this->base32_encode($random);
	}

	// Erstellt eine Provisioning-URI für Authenticator-Apps (otpauth://totp/...)
	protected function generateTOTPProvisioningUri(string $label, string $secret, string $issuer = 'Nauticstore24'): string
	{
		$account = rawurlencode($issuer . ':' . $label);
		$qs = http_build_query([
			'secret' => $secret,
			'issuer' => $issuer,
			'algorithm' => 'SHA1',
			'digits' => 6,
			'period' => 30
		]);
		return "otpauth://totp/{$account}?{$qs}";
	}

	// Verifiziert einen TOTP-Code gegen das Secret (Fenster +/- 1 Intervall)
	protected function verifyTOTP(string $secret, string $code, int $window = 1): bool
	{
		$code = trim($code);
		if ($code === '') return false;
		$secretBin = $this->base32_decode($secret);
		if ($secretBin === false) return false;

		$timeStep = floor(time() / 30);
		for ($i = -$window; $i <= $window; $i++) {
			$ts = $timeStep + $i;
			$packet = pack('N*', 0) . pack('N*', $ts); // 64-bit BE
			$hash = hash_hmac('sha1', $packet, $secretBin, true);
			$offset = ord(substr($hash, -1)) & 0x0F;
			$truncated = (
				((ord($hash[$offset]) & 0x7f) << 24) |
				((ord($hash[$offset + 1]) & 0xff) << 16) |
				((ord($hash[$offset + 2]) & 0xff) << 8) |
				(ord($hash[$offset + 3]) & 0xff)
			);
			$calculated = $truncated % 1000000;
			if (str_pad((string)$calculated, 6, '0', STR_PAD_LEFT) === str_pad($code, 6, '0', STR_PAD_LEFT)) {
				return true;
			}
		}
		return false;
	}

	// --- Hilfsfunktionen Base32 ---
	protected function base32_encode(string $data): string
	{
		$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$bits = '';
		$output = '';
		for ($i = 0, $len = strlen($data); $i < $len; $i++) {
			$bits .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
		}
		$chunks = str_split($bits, 5);
		foreach ($chunks as $chunk) {
			if (strlen($chunk) < 5) $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
			$output .= $alphabet[bindec($chunk)];
		}
		// Optional: padding can be omitted for TOTP secrets
		return $output;
	}

	protected function base32_decode(string $b32)
	{
		$b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32));
		if ($b32 === '') return '';
		$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$bits = '';
		for ($i = 0, $len = strlen($b32); $i < $len; $i++) {
			$pos = strpos($alphabet, $b32[$i]);
			if ($pos === false) return false;
			$bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
		}
		$bytes = str_split($bits, 8);
		$out = '';
		foreach ($bytes as $byte) {
			if (strlen($byte) < 8) continue;
			$out .= chr(bindec($byte));
		}
		return $out;
	}

}
