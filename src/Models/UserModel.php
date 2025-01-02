<?php
/**
 * src/Models/UserModel.php
 */

namespace App\Models;

use OTPHP\TOTP;

require_once __DIR__ . '/Database.php';

class UserModel
{
    /**
     * Registrieren eines neuen Nutzers
     */
    public static function register($email, $password, $isAdmin = 0)
    {
        $pdo = Database::getConnection();
        
        // Passwort sicher hashen
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (email, password, is_admin) VALUES (?, ?, ?)");
        return $stmt->execute([$email, $hash, $isAdmin]);
    }

    /**
     * Nutzer anhand E-Mail finden
     */
    public static function getByEmail($email)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(); // gibt das Array mit User-Daten zurück (oder false)
    }

    /**
     * Nutzer anhand ID finden
     */
    public static function getById($id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Überprüfen von E-Mail/Passwort
     */
    public static function verifyLogin($email, $password)
    {
        $user = self::getByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            return $user; // Gibt das vollständige User-Array zurück
        }
        return false;
    }
}
