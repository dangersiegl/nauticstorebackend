<?php

/**

 * src/Controllers/UserController.php

 */

use App\Models\UserModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



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
        // 1) URL auswerten / Section erkennen
        $parts = explode('/', trim($_GET['route'] ?? '', '/'));
        // Mögliche Formen:
        // /user/edit -> eigenes Profil
        // /user/edit/password -> eigenes Passwort
        // /user/edit/{id} -> Admin edit user
        // /user/edit/{id}/password -> Admin edit user's password
        $section = '';
        $userId = 0;

        if (isset($parts[2])) {
            if (is_numeric($parts[2])) {
                $userId = (int)$parts[2];
                // evtl. /user/edit/{id}/password
                if (isset($parts[3]) && $parts[3] === 'password') {
                    $section = 'password';
                }
            } else {
                // z. B. /user/edit/password
                if ($parts[2] === 'password') {
                    $section = 'password';
                } else {
                    // fallback: nichts besonderes, treat as no id
                }
            }
        }

        // Wenn noch keine ID bestimmt, verwende eingeloggten User (eigener Account)
        if (empty($userId)) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userId = $_SESSION['user_id'] ?? 0;
        }

        // Falls weiterhin keine ID bestimmbar, Fehler
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

         // --- Neues: Prüfe auf ausstehende E-Mail-Änderung (email_resets) und bereite View-Variablen vor ---
         $pendingEmail = null;
         $pendingExpires = null; // Sekunden bis Ablauf
         $pendingToken = null;
         $pendingRowId = null;
         $resendUrl = '/user/resend-confirm-email'; // View kann diesen Link verwenden

         // PDO identisch wie an anderen Stellen ermitteln
         $pdo = null;
         if (class_exists('\App\Models\Database')) {
             $cls = '\App\Models\Database';
             if (method_exists($cls, 'getConnection')) {
                 try { $pdo = $cls::getConnection(); } catch (\Throwable $e) { $pdo = null; }
             }
             if ($pdo === null && method_exists($cls,'getInstance')) {
                 try { $inst = $cls::getInstance(); if (is_object($inst) && method_exists($inst,'getConnection')) $pdo = $inst->getConnection(); } catch (\Throwable $e) { $pdo = null; }
             }
         }
         if ($pdo === null && class_exists('Database')) {
             $cls = 'Database';
             if (method_exists($cls, 'getConnection')) {
                 try { $pdo = $cls::getConnection(); } catch (\Throwable $e) { $pdo = null; }
             }
             if ($pdo === null && method_exists($cls,'getInstance')) {
                 try { $inst = $cls::getInstance(); if (is_object($inst) && method_exists($inst,'getConnection')) $pdo = $inst->getConnection(); } catch (\Throwable $e) { $pdo = null; }
             }
         }
         if ($pdo === null && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof \PDO) $pdo = $GLOBALS['pdo'];
         if ($pdo === null && !empty($GLOBALS['db']) && $GLOBALS['db'] instanceof \PDO) $pdo = $GLOBALS['db'];
         if ($pdo === null && !empty($GLOBALS['database']) && $GLOBALS['database'] instanceof \PDO) $pdo = $GLOBALS['database'];

         if ($pdo instanceof \PDO) {
             try {
                 $sql = "SELECT id, new_email, token, created_at FROM email_resets WHERE user_id = :id ORDER BY created_at DESC LIMIT 1";
                 $stmt = $pdo->prepare($sql);
                 $stmt->execute([':id' => $userId]);
                 $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                 if (!empty($row['new_email'])) {
                     $pendingEmail = $row['new_email'];
                     $pendingToken = $row['token'];
                     $pendingRowId = $row['id'];
                     $createdTs = strtotime($row['created_at']);
                     $expiresAt = ($createdTs !== false) ? ($createdTs + 48*3600) : null; // 48h wie früher
                     $pendingExpires = ($expiresAt !== null) ? max(0, $expiresAt - time()) : null;
                 }
             } catch (\Throwable $e) {
                 // ignore - View zeigt nichts in diesem Fall
             }
         }
 
        if (empty($section)) {
            $section = $_GET['section'] ?? '';
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
                 $changed = $this->updatePasswordInternal($userId, $newPassword);

                 if ($changed) {
                     $success = "Passwort wurde erfolgreich geändert.";
                 } else {
                     // Falls updatePasswordInternal() eine genauere Fehlermeldung gesetzt hat, zeige sie an
                     if (session_status() === PHP_SESSION_NONE) session_start();
                     $sessErr = !empty($_SESSION['error_message']) ? $_SESSION['error_message'] : null;
                     if ($sessErr) {
                         $error = "Fehler beim Ändern des Passworts: " . $sessErr;
                         unset($_SESSION['error_message']);
                     } else {
                         $error = "Fehler beim Ändern des Passworts.";
                     }
                 }

                 // Lade View erneut
                 require __DIR__ . '/../Views/user/edit.php';
                 return;
             }

             // 2) Allgemeine Profildaten ändern (E-Mail, ggf. is_admin)
             $email = $_POST['email'] ?? '';
            // Neu: Vorname / Nachname
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName  = trim($_POST['last_name'] ?? '');

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

            // Update in DB (zuerst Model-Versuch)
            try {
                if (class_exists('UserModel') && method_exists('UserModel','update')) {
                    // vorhandenes Model verwenden (ändert ggf. nur email/is_admin)
                    UserModel::update($userId, $email, $isAdminFlag);
                }
            } catch (\Throwable $e) {
                // ignore, versuchen wir ggf. Fallbacks weiter unten
            }
            
            // Falls Vor- oder Nachname gesetzt wurden: versuche sie zu speichern (Model-Funktionen oder direkter SQL-Fallback).
            // --- E-Mail-Änderung: Wenn sich die E-Mail ändert, prüfe zuerst Verfügbarkeit
            $emailChanged = ($email !== ($user['email'] ?? ''));
            if ($emailChanged) {
                // Prüfe, ob E-Mail bereits existiert (außer beim eigenen Account)
                $existing = null;
                try { $existing = UserModel::getByEmail($email); } catch (\Throwable $e) { $existing = null; }
                if (!empty($existing) && (!isset($existing['id']) || $existing['id'] != $userId)) {
                    $error = "Diese E-Mail-Adresse wird bereits verwendet.";
                    require __DIR__ . '/../Views/user/edit.php';
                    return;
                }

                // Erzeuge Token und speichere in email_resets (Model-Versuch, sonst SQL-Fallback)
                $token = bin2hex(random_bytes(16));
                $savedPending = false;

                // Versuche Model-Methode first (wenn vorhanden)
                if (class_exists('UserModel') && method_exists('UserModel','requestEmailChange')) {
                    try {
                        $savedPending = (bool) UserModel::requestEmailChange($userId, $email, $token);
                    } catch (\Throwable $e) {
                        $savedPending = false;
                    }
                }

                if (!$savedPending) {
                    // SQL-Fallback: insert into email_resets
                    $pdo = null;
                    // Versuche verschiedene Wege auf die PDO-Connection zu kommen
                    if (class_exists('\App\Models\Database')) {
                        $cls = '\App\Models\Database';
                        if (method_exists($cls, 'getConnection')) {
                            try { $pdo = $cls::getConnection(); } catch (\Throwable $e) { $pdo = null; }
                        }
                        if ($pdo === null && method_exists($cls,'getInstance')) {
                            try { $inst = $cls::getInstance(); if (is_object($inst) && method_exists($inst,'getConnection')) $pdo = $inst->getConnection(); } catch (\Throwable $e) { $pdo = null; }
                        }
                    }
                    if ($pdo === null && class_exists('Database')) {
                        $cls = 'Database';
                        if (method_exists($cls, 'getConnection')) {
                            try { $pdo = $cls::getConnection(); } catch (\Throwable $e) { $pdo = null; }
                        }
                        if ($pdo === null && method_exists($cls,'getInstance')) {
                            try { $inst = $cls::getInstance(); if (is_object($inst) && method_exists($inst,'getConnection')) $pdo = $inst->getConnection(); } catch (\Throwable $e) { $pdo = null; }
                        }
                    }
                    if ($pdo === null && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof \PDO) $pdo = $GLOBALS['pdo'];
                    if ($pdo === null && !empty($GLOBALS['db']) && $GLOBALS['db'] instanceof \PDO) $pdo = $GLOBALS['db'];
                    if ($pdo === null && !empty($GLOBALS['database']) && $GLOBALS['database'] instanceof \PDO) $pdo = $GLOBALS['database'];

                    if ($pdo instanceof \PDO) {
                        try {
                            // optional alte Einträge für diesen user löschen
                            $pdo->beginTransaction();
                            $del = $pdo->prepare("DELETE FROM email_resets WHERE user_id = :uid");
                            $del->execute([':uid' => $userId]);

                            $sql = "INSERT INTO email_resets (user_id, new_email, token, created_at) VALUES (:uid, :email, :token, :created_at)";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([
                                ':uid' => $userId,
                                ':email' => $email,
                                ':token' => $token,
                                ':created_at' => date('Y-m-d H:i:s')
                            ]);
                            $pdo->commit();
                            $savedPending = true;
                        } catch (\Throwable $e) {
                            if ($pdo->inTransaction()) $pdo->rollBack();
                            $savedPending = false;
                        }
                    }
                }

                if ($savedPending) {
                    // Sende Bestätigungsmail an die neue Adresse (PHPMailer/SMTP konfigurierbar)
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $confirmUrl = $scheme . '://' . $host . '/user/confirm-email?uid=' . urlencode($userId) . '&token=' . urlencode($token);
                    $subject = 'E‑Mail Änderung bestätigen';
                    $message = "Hallo,\n\nbitte bestätigen Sie die Änderung Ihrer E‑Mailadresse, indem Sie auf den folgenden Link klicken:\n\n" . $confirmUrl . "\n\nWenn Sie diese Änderung nicht angefordert haben, ignorieren Sie bitte diese Nachricht.\n\nFreundliche Grüße\n";

                    // From-Defaults vorbereiten (wird auch im Fallback verwendet)
                    $fromAddr = (defined('SMTP_FROM') && SMTP_FROM !== '') ? SMTP_FROM : ('noreply@' . $host);
                    $fromName = (defined('SMTP_FROM_NAME') && SMTP_FROM_NAME !== '') ? SMTP_FROM_NAME : 'NoReply';

                    // Verwende PHPMailer, falls vorhanden; konfiguriere SMTP aus config
                    if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
                        try {
                            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

                            if (defined('SMTP_HOST') && SMTP_HOST !== '') {
                                $mail->isSMTP();
                                $mail->Host = SMTP_HOST;
                                $mail->Port = (defined('SMTP_PORT') && is_numeric(SMTP_PORT)) ? (int)SMTP_PORT : 25;
                                $mail->SMTPAuth = defined('SMTP_AUTH') ? (bool)SMTP_AUTH : true;
                                if (!empty(SMTP_USER)) $mail->Username = SMTP_USER;
                                if (!empty(SMTP_PASS)) $mail->Password = SMTP_PASS;

                                // Verschlüsselung mapping
                                $sec = defined('SMTP_SECURE') ? strtolower(SMTP_SECURE) : '';
                                if ($sec === 'ssl') {
                                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                                } elseif ($sec === 'tls') {
                                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                                } else {
                                    $mail->SMTPSecure = '';
                                }

                                // Debug bzw. Debugoutput ins error_log (konfigurierbar via SMTP_DEBUG)
                                $mail->SMTPDebug = defined('SMTP_DEBUG') ? (int)SMTP_DEBUG : 0;
                                $mail->Debugoutput = function($str, $level) {
                                    error_log("PHPMailer debug[$level]: $str");
                                };

                                // Optional: allow self-signed (Testserver)
                                if (defined('SMTP_ALLOW_SELF_SIGNED') && SMTP_ALLOW_SELF_SIGNED) {
                                    $mail->SMTPOptions = [
                                        'ssl' => [
                                            'verify_peer' => false,
                                            'verify_peer_name' => false,
                                            'allow_self_signed' => true
                                        ]
                                    ];
                                }
                            } else {
                                // kein SMTP konfiguriert -> mail() Transport
                                $mail->isMail();
                            }

                            // From/To/Content
                            $mail->setFrom($fromAddr, $fromName);
                            $mail->addAddress($email);
                            $mail->Subject = $subject;
                            $mail->Body    = $message;
                            $mail->AltBody = $message;

                            $mail->send();
                            // Erfolg: nichts weiter nötig
                        } catch (\Throwable $e) {
                            // Loggen und Fallback auf mail()
                            error_log("PHPMailer send error: " . $e->getMessage());
                            $headers = 'From: ' . $fromAddr . "\r\n" .
                                       'Reply-To: ' . $fromAddr . "\r\n" .
                                       'X-Mailer: PHP/' . phpversion();
                            $res = @mail($email, $subject, $message, $headers);
                            if (!$res) {
                                error_log("mail() fallback failed for $email");
                            }
                        }
                    } else {
                        // Kein PHPMailer installiert -> einfacher mail() Call
                        $headers = 'From: ' . $fromAddr . "\r\n" .
                                   'Reply-To: ' . $fromAddr . "\r\n" .
                                   'X-Mailer: PHP/' . phpversion();
                        $res = @mail($email, $subject, $message, $headers);
                        if (!$res) error_log("mail() failed for $email");
                    }

                    $success = "Eine Bestätigungsmail wurde an " . htmlspecialchars($email) . " gesendet. Die neue E‑Mail wird nach Bestätigung übernommen.";
                    // Markiere, dass eine E-Mail-Änderung pending ist damit wir die generische "Daten gespeichert." Meldung nicht überschreiben
                    $emailChangePending = true;
                    // Sofort View-Variablen setzen, damit die Seite die NEUE pending E-Mail sofort anzeigt (kein manueller Reload erforderlich)
                    $pendingEmail = $email;
                    $pendingToken = $token;
                    // Restlaufzeit in Sekunden (approx. 48 Stunden)
                    $pendingExpires = 48 * 3600;
                 } else {
                     $error = "Fehler beim Anlegen der Änderungsbestätigung. Bitte versuchen Sie es später.";
                     require __DIR__ . '/../Views/user/edit.php';
                     return;
                 }
            } else {
                // E-Mail nicht geändert -> normale Speicherung per Model (wenn vorhanden)
                try {
                    if (class_exists('UserModel') && method_exists('UserModel','update')) {
                        UserModel::update($userId, $email, $isAdminFlag);
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            // Falls Vor- oder Nachname gesetzt wurden: versuche sie zu speichern (Model-Funktionen oder direkter SQL-Fallback).
             if ($firstName !== '' || $lastName !== '') {
                 $namesSaved = false;
                 if (class_exists('UserModel')) {
                     // gängige Hilfsmethoden prüfen
                     if (method_exists('UserModel','setName')) {
                         try { $namesSaved = (bool) UserModel::setName($userId, $firstName, $lastName); } catch (\Throwable $e) { $namesSaved = false; }
                     } elseif (method_exists('UserModel','updateNames')) {
                         try { $namesSaved = (bool) UserModel::updateNames($userId, $firstName, $lastName); } catch (\Throwable $e) { $namesSaved = false; }
                     }
                 }
                 if (!$namesSaved) {
                     // Direkter SQL-Fallback (Versuch, PDO wie in updatePasswordInternal zu holen)
                     $pdo = null;
                     if (class_exists('\App\Models\Database')) {
                         $cls = '\App\Models\Database';
                         if (method_exists($cls, 'getConnection')) {
                             try { $pdo = $cls::getConnection(); } catch (\Throwable $e) { $pdo = null; }
                         }
                         if ($pdo === null && method_exists($cls,'getInstance')) {
                             try { $inst = $cls::getInstance(); if (is_object($inst) && method_exists($inst,'getConnection')) $pdo = $inst->getConnection(); } catch (\Throwable $e) { $pdo = null; }
                         }
                     }
                     if ($pdo === null && class_exists('Database')) {
                         $cls = 'Database';
                         if (method_exists($cls, 'getConnection')) {
                             try { $pdo = $cls::getConnection(); } catch (\Throwable $e) { $pdo = null; }
                         }
                         if ($pdo === null && method_exists($cls,'getInstance')) {
                             try { $inst = $cls::getInstance(); if (is_object($inst) && method_exists($inst,'getConnection')) $pdo = $inst->getConnection(); } catch (\Throwable $e) { $pdo = null; }
                         }
                     }
                     if ($pdo === null && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof \PDO) $pdo = $GLOBALS['pdo'];
                     if ($pdo === null && !empty($GLOBALS['db']) && $GLOBALS['db'] instanceof \PDO) $pdo = $GLOBALS['db'];
                     if ($pdo === null && !empty($GLOBALS['database']) && $GLOBALS['database'] instanceof \PDO) $pdo = $GLOBALS['database'];
 
                     if ($pdo instanceof \PDO) {
                         try {
                             $sql = "UPDATE users_users SET first_name = :fn, last_name = :ln WHERE id = :id";
                             $stmt = $pdo->prepare($sql);
                             $stmt->execute([':fn' => $firstName, ':ln' => $lastName, ':id' => $userId]);
                         } catch (\Throwable $e) {
                             // optional: error_log($e->getMessage());
                         }
                     }
                 }
             }

            // Bei eigenem Profil ggf. Session-Email aktualisieren (falls benötigt)
            // Session nur aktualisieren, wenn die E-Mail tatsächlich übernommen wurde (also NICHT bei pending change).
            if ($currentUserId === $userId && empty($emailChanged)) {
                $_SESSION['user_email'] = $email;
            }

            // Zurück zur Liste oder Profilseite
            if ($currentUserIsAdmin && ($currentUserId != $userId)) {
                header('Location: /user/list');
                exit;
            } else {
                // Wenn eine E-Mail-Änderung aussteht, lasse die bereits gesetzte $success-Meldung stehen.
                if (empty($emailChangePending)) {
                    $success = "Daten gespeichert.";
                }
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

        // Aktuellen Benutzer laden (für TOTP-Secret und Darstellung)
        $user = UserModel::getById($userId);
        $mfa_enabled = !empty($user['totp_secret']);

        $password = $_POST['password'] ?? '';
        $totp     = trim($_POST['totp'] ?? '');

        // Validierung beider Felder
        if (empty($password) || empty($totp)) {
            $error = "Bitte Passwort und aktuellen TOTP-Code eingeben.";
            require_once __DIR__ . '/../Views/mfa/enable.php';
            return;
        }

        // Passwort prüfen
        if (!UserModel::verifyPassword($userId, $password)) {
            $error = "Passwort falsch. MFA wurde nicht deaktiviert.";
            require_once __DIR__ . '/../Views/mfa/enable.php';
            return;
        }

        // TOTP prüfen (nutze gespeichertes Secret)
        if (empty($user['totp_secret']) || !$this->verifyTOTP($user['totp_secret'], $totp)) {
            $error = "Ungültiger TOTP-Code. MFA wurde nicht deaktiviert.";
            require_once __DIR__ . '/../Views/mfa/enable.php';
            return;
        }

        // Beides korrekt -> MFA deaktivieren
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

	// Neues: internes Update-Passwort Helper (versucht vorhandene Database-Konzepte zu nutzen)
	protected function updatePasswordInternal(int $userId, string $newPassword): bool
	{
		// Hash erstellen
		$hash = password_hash($newPassword, PASSWORD_DEFAULT);
		if ($hash === false) {
			if (session_status() === PHP_SESSION_NONE) session_start();
			$_SESSION['error_message'] = 'Fehler beim Erstellen des Passwort-Hashes.';
			return false;
		}

		// 1) Versuche Model-Methoden, falls vorhanden
		if (class_exists('UserModel') || class_exists('\App\Models\UserModel')) {
			$modelClassCandidates = [];
			if (class_exists('\App\Models\UserModel')) $modelClassCandidates[] = '\App\Models\UserModel';
			if (class_exists('UserModel')) $modelClassCandidates[] = 'UserModel';

			foreach ($modelClassCandidates as $mc) {
				// updatePassword($id, $hash)
				if (method_exists($mc, 'updatePassword')) {
					try {
						$res = $mc::updatePassword($userId, $hash);
						return (bool)$res;
					} catch (\Throwable $e) {
						// weiter zu nächsten Versuchen
					}
				}
				// setPassword($id, $hash)
				if (method_exists($mc, 'setPassword')) {
					try {
						$res = $mc::setPassword($userId, $hash);
						return (bool)$res;
					} catch (\Throwable $e) {
						// weiter
					}
				}
				// update($id, $email, $isAdmin) eventuell vorhanden, aber nicht passend für Passwort
			}
		}

		// 2) Versuche PDO über verschiedene Database-Klassen / globale Variablen zu erhalten
		$pdo = null;

		// namespaced Database
		if (class_exists('\App\Models\Database')) {
			$cls = '\App\Models\Database';
			// häufige Patterns: getConnection(), getInstance()->getConnection()
			if (method_exists($cls, 'getConnection')) {
				try { $pdo = $cls::getConnection(); } catch (\Throwable $e) { $pdo = null; }
			}
			if ($pdo === null && method_exists($cls, 'getInstance')) {
				try {
					$inst = $cls::getInstance();
					if (is_object($inst) && method_exists($inst, 'getConnection')) {
						$pdo = $inst->getConnection();
					}
				} catch (\Throwable $e) { $pdo = null; }
			}
		}

		// legacy Database (global namespace)
		if ($pdo === null && class_exists('Database')) {
			$cls = 'Database';
			if (method_exists($cls, 'getConnection')) {
				try { $pdo = $cls::getConnection(); } catch (\Throwable $e) { $pdo = null; }
			}
			if ($pdo === null && method_exists($cls, 'getInstance')) {
				try {
					$inst = $cls::getInstance();
					if (is_object($inst) && method_exists($inst, 'getConnection')) {
						$pdo = $inst->getConnection();
					}
				} catch (\Throwable $e) { $pdo = null; }
			}
		}

		// globales $pdo prüfen
		if ($pdo === null && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof \PDO) {
			$pdo = $GLOBALS['pdo'];
		}

		// weiteres Fallback: $GLOBALS['db'] oder $GLOBALS['database']
		if ($pdo === null && !empty($GLOBALS['db']) && $GLOBALS['db'] instanceof \PDO) $pdo = $GLOBALS['db'];
		if ($pdo === null && !empty($GLOBALS['database']) && $GLOBALS['database'] instanceof \PDO) $pdo = $GLOBALS['database'];

		if (!($pdo instanceof \PDO)) {
			if (session_status() === PHP_SESSION_NONE) session_start();
			$_SESSION['error_message'] = 'Fehler beim Ändern des Passworts: keine DB-Verbindung gefunden.';
			return false;
		}

		// 3) Direkter SQL-Update
		try {
			$sql = "UPDATE users_users SET password = :pw WHERE id = :id";
			$stmt = $pdo->prepare($sql);
			$stmt->execute([':pw' => $hash, ':id' => $userId]);
			// rowCount kann 0 sein, wenn der Hash identisch war; behandeln als Erfolg wenn kein Exception
			return true;
		} catch (\Throwable $e) {
			if (session_status() === PHP_SESSION_NONE) session_start();
			// Speichere die genaue Exception-Meldung kurz in der Session für Debug-Ausgabe
			$_SESSION['error_message'] = 'DB-Fehler: ' . $e->getMessage();
             // optional: error_log($e->getMessage());
             return false;
         }
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

	// Generiert die URI für den QR-Code (otpauth://)
	// $label: z.B. user email oder "user@example.com"
	// $secret: Base32-Secret (ohne padding ist in Ordnung)
	// $issuer: optional, Name des Dienstes (wird in der URI als issuer gesetzt)
	protected function generateTOTPProvisioningUri(string $label, string $secret, string $issuer = 'Nauticstore24'): string
	{
		// Stelle sicher, dass Secret Base32 und Großbuchstaben sind
		$secret = strtoupper(trim($secret, '='));
		// Label und Issuer URL-encoden
		$labelEnc  = rawurlencode($label);
		$issuerEnc = rawurlencode($issuer);

		// Standard-Parameter für TOTP (SHA1, 6 Stellen, 30s)
		$params = [
			'secret'    => $secret,
			'issuer'    => $issuer,
			'algorithm' => 'SHA1',
			'digits'    => '6',
			'period'    => '30'
		];

		$query = http_build_query($params);
		// otpauth://totp/Issuer:Label?secret=...&issuer=Issuer...
		return 'otpauth://totp/' . $issuerEnc . ':' . $labelEnc . '?' . $query;
	}

    // Neues: Bestätigungs-Endpunkt für E‑Mail-Änderungen
    public function confirmEmail()
    {
        // Beispiel-URL: /user/confirm-email?uid=123&token=abc
        $uid = $_GET['uid'] ?? null;
        $token = $_GET['token'] ?? null;

        if (empty($uid) || empty($token)) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error_message'] = 'Ungültiger Bestätigungslink.';
            header('Location: /user/edit');
            exit;
        }

        $uid = (int)$uid;
        $confirmed = false;

        // Versuche Model-Methode zuerst (falls vorhanden)
        if (class_exists('UserModel') && method_exists('UserModel','confirmEmailChange')) {
            try {
                $confirmed = (bool) UserModel::confirmEmailChange($uid, $token);
            } catch (\Throwable $e) {
                $confirmed = false;
            }
        }

        if (!$confirmed) {
            // SQL-Fallback gegen email_resets Tabelle
            $pdo = null;
            if (class_exists('\App\Models\Database')) {
                $cls = '\App\Models\Database';
                if (method_exists($cls, 'getConnection')) {
                    try { $pdo = $cls::getConnection(); } catch (\Throwable $e) { $pdo = null; }
                }
                if ($pdo === null && method_exists($cls,'getInstance')) {
                    try { $inst = $cls::getInstance(); if (is_object($inst) && method_exists($inst,'getConnection')) $pdo = $inst->getConnection(); } catch (\Throwable $e) { $pdo = null; }
                }
            }
            if ($pdo === null && class_exists('Database')) {
                $cls = 'Database';
                if (method_exists($cls, 'getConnection')) {
                    try { $pdo = $cls::getConnection(); } catch (\Throwable $e) { $pdo = null; }
                }
                if ($pdo === null && method_exists($cls,'getInstance')) {
                    try { $inst = $cls::getInstance(); if (is_object($inst) && method_exists($inst,'getConnection')) $pdo = $inst->getConnection(); } catch (\Throwable $e) { $pdo = null; }
                }
            }
            if ($pdo === null && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof \PDO) $pdo = $GLOBALS['pdo'];
            if ($pdo === null && !empty($GLOBALS['db']) && $GLOBALS['db'] instanceof \PDO) $pdo = $GLOBALS['db'];
            if ($pdo === null && !empty($GLOBALS['database']) && $GLOBALS['database'] instanceof \PDO) $pdo = $GLOBALS['database'];

            if ($pdo instanceof \PDO) {
                try {
                    $sql = "SELECT id, new_email, created_at FROM email_resets WHERE user_id = :id AND token = :pt LIMIT 1";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':id' => $uid, ':pt' => $token]);
                    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if (!empty($row['new_email'])) {
                        // Prüfe Ablauf (z.B. 48 Stunden)
                        $created = strtotime($row['created_at']);
                        if ($created !== false && ($created + 48*3600) >= time()) {
                            // Update users_users.email und lösche Eintrag in email_resets
                            $pdo->beginTransaction();
                            $sqlUp = "UPDATE users_users SET email = :newemail WHERE id = :id";
                            $stmt2 = $pdo->prepare($sqlUp);
                            $stmt2->execute([':newemail' => $row['new_email'], ':id' => $uid]);

                            $del = $pdo->prepare("DELETE FROM email_resets WHERE id = :rid");
                            $del->execute([':rid' => $row['id']]);

                            $pdo->commit();
                            $confirmed = true;

                            // Falls der aktuell eingeloggte User seine eigene E-Mail bestätigt hat -> Session aktualisieren
                            if (session_status() === PHP_SESSION_NONE) session_start();
                            if (!empty($_SESSION['user_id']) && $_SESSION['user_id'] == $uid) {
                                $_SESSION['user_email'] = $row['new_email'];
                            }
                        } else {
                            // Token abgelaufen -> entferne Eintrag
                            $del = $pdo->prepare("DELETE FROM email_resets WHERE id = :rid");
                            $del->execute([':rid' => $row['id']]);
                            $confirmed = false;
                        }
                    }
                } catch (\Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $confirmed = false;
                }
            }
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($confirmed) {
            $_SESSION['success_message'] = 'E‑Mail erfolgreich bestätigt und übernommen.';
        } else {
            $_SESSION['error_message'] = 'Bestätigung fehlgeschlagen oder Link ungültig.';
        }

        header('Location: /user/edit');
        exit;
    }

    // Resend confirmation mail for pending email change
    public function resendConfirmEmail()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $currentUserId = $_SESSION['user_id'] ?? null;
        $isAdmin = !empty($_SESSION['is_admin']);

        // UID kann als GET oder POST kommen; Standard auf aktuellen User
        $uid = isset($_REQUEST['uid']) ? (int)$_REQUEST['uid'] : $currentUserId;
        if (empty($uid)) {
            $_SESSION['error_message'] = 'Ungültige Anfrage.';
            header('Location: /user/edit');
            exit;
        }
        if ($uid !== $currentUserId && !$isAdmin) {
            $_SESSION['error_message'] = 'Keine Berechtigung.';
            header('Location: /user/edit');
            exit;
        }

        // PDO ermitteln (wie in anderen Methoden)
        $pdo = null;
        if (class_exists('\App\Models\Database')) {
            $cls = '\App\Models\Database';
            if (method_exists($cls, 'getConnection')) {
                try { $pdo = $cls::getConnection(); } catch (\Throwable $e) { $pdo = null; }
            }
            if ($pdo === null && method_exists($cls, 'getInstance')) {
                try { $inst = $cls::getInstance(); if (is_object($inst) && method_exists($inst,'getConnection')) $pdo = $inst->getConnection(); } catch (\Throwable $e) { $pdo = null; }
            }
        }
        if ($pdo === null && class_exists('Database')) {
            $cls = 'Database';
            if (method_exists($cls, 'getConnection')) {
                try { $pdo = $cls::getConnection(); } catch (\Throwable $e) { $pdo = null; }
            }
            if ($pdo === null && method_exists($cls, 'getInstance')) {
                try { $inst = $cls::getInstance(); if (is_object($inst) && method_exists($inst,'getConnection')) $pdo = $inst->getConnection(); } catch (\Throwable $e) { $pdo = null; }
            }
        }
        if ($pdo === null && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof \PDO) $pdo = $GLOBALS['pdo'];
        if ($pdo === null && !empty($GLOBALS['db']) && $GLOBALS['db'] instanceof \PDO) $pdo = $GLOBALS['db'];
        if ($pdo === null && !empty($GLOBALS['database']) && $GLOBALS['database'] instanceof \PDO) $pdo = $GLOBALS['database'];

        if (!($pdo instanceof \PDO)) {
            $_SESSION['error_message'] = 'Keine DB-Verbindung vorhanden.';
            header('Location: /user/edit');
            exit;
        }

        try {
            $sql = "SELECT id, new_email, token FROM email_resets WHERE user_id = :uid ORDER BY created_at DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $uid]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (empty($row['new_email'])) {
                $_SESSION['error_message'] = 'Keine ausstehende E‑Mail‑Änderung gefunden.';
                header('Location: /user/edit' . ($isAdmin && $uid !== $currentUserId ? '/' . $uid : ''));
                exit;
            }

            // Optional: neuen Token generieren statt reuse (sicherer) — aktuell reuse
            $email = $row['new_email'];
            $token = $row['token'];

            // Reset created_at, damit Ablauf neu startet
            $upd = $pdo->prepare("UPDATE email_resets SET created_at = :ca WHERE id = :id");
            $upd->execute([':ca' => date('Y-m-d H:i:s'), ':id' => $row['id']]);

            // Sende Bestätigungsmail (PHPMailer/Fallback) - gleicht vorhandener Logik
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $confirmUrl = $scheme . '://' . $host . '/user/confirm-email?uid=' . urlencode($uid) . '&token=' . urlencode($token);
            $subject = 'E‑Mail Änderung bestätigen';
            $message = "Hallo,\n\nbitte bestätigen Sie die Änderung Ihrer E‑Mailadresse, indem Sie auf den folgenden Link klicken:\n\n" . $confirmUrl . "\n\nWenn Sie diese Änderung nicht angefordert haben, ignorieren Sie bitte diese Nachricht.\n\nFreundliche Grüße\n";

            $fromAddr = (defined('SMTP_FROM') && SMTP_FROM !== '') ? SMTP_FROM : ('noreply@' . $host);
            $fromName = (defined('SMTP_FROM_NAME') && SMTP_FROM_NAME !== '') ? SMTP_FROM_NAME : 'NoReply';

            if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
                try {
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    if (defined('SMTP_HOST') && SMTP_HOST !== '') {
                        $mail->isSMTP();
                        $mail->Host = SMTP_HOST;
                        $mail->Port = (defined('SMTP_PORT') && is_numeric(SMTP_PORT)) ? (int)SMTP_PORT : 25;
                        $mail->SMTPAuth = defined('SMTP_AUTH') ? (bool)SMTP_AUTH : true;
                        if (!empty(SMTP_USER)) $mail->Username = SMTP_USER;
                        if (!empty(SMTP_PASS)) $mail->Password = SMTP_PASS;
                        $sec = defined('SMTP_SECURE') ? strtolower(SMTP_SECURE) : '';
                        if ($sec === 'ssl') $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                        elseif ($sec === 'tls') $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->SMTPDebug = defined('SMTP_DEBUG') ? (int)SMTP_DEBUG : 0;
                        $mail->Debugoutput = function($str, $level) { error_log("PHPMailer debug[$level]: $str"); };
                        if (defined('SMTP_ALLOW_SELF_SIGNED') && SMTP_ALLOW_SELF_SIGNED) {
                            $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
                        }
                    } else {
                        $mail->isMail();
                    }
                    $mail->setFrom($fromAddr, $fromName);
                    $mail->addAddress($email);
                    $mail->Subject = $subject;
                    $mail->Body = $message;
                    $mail->AltBody = $message;
                    $mail->send();
                    $_SESSION['success_message'] = 'Bestätigungsmail wurde erneut versendet.';
                } catch (\Throwable $e) {
                    error_log("PHPMailer resend error: " . $e->getMessage());
                    $headers = 'From: ' . $fromAddr . "\r\n" . 'Reply-To: ' . $fromAddr . "\r\n";
                    @mail($email, $subject, $message, $headers);
                    $_SESSION['success_message'] = 'Bestätigungsmail wurde erneut versendet.';
                }
            } else {
                $headers = 'From: ' . $fromAddr . "\r\n" . 'Reply-To: ' . $fromAddr . "\r\n";
                @mail($email, $subject, $message, $headers);
                $_SESSION['success_message'] = 'Bestätigungsmail wurde erneut versendet.';
            }

        } catch (\Throwable $e) {
            $_SESSION['error_message'] = 'Fehler beim Erneut-Versenden: ' . $e->getMessage();
        }

        // Redirect zurück zur Edit-Seite des betroffenen Users
        if ($isAdmin && $uid !== $currentUserId) {
            header('Location: /user/edit/' . $uid);
        } else {
            header('Location: /user/edit');
        }
        exit;
    }
}
