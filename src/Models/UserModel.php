<?php
/**
 * src/Models/UserModel.php
 */

namespace App\Models;

use OTPHP\TOTP;


class UserModel
{
    /**
     * Neuen Nutzer anlegen
     */
    public static function register($email, $password, $totpSecret = null, $isAdmin = 0)
    {
        $pdo = Database::getConnection();

        // Passwort sicher hashen
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO users_users (email, password, totp_secret, is_admin, created_at) VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$email, $hash, $totpSecret, $isAdmin]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Alle Nutzer laden
     */
    public static function getAll()
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM users_users");
        return $stmt->fetchAll();
    }

    /**
     * Nutzer anhand E-Mail finden
     */
    public static function getByEmail($email)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users_users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(); // gibt das Array mit User-Daten zurück (oder false)
    }

    /**
     * Nutzer anhand ID finden
     */
    public static function getById($id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users_users WHERE id = ?");
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

    /**
     * Nutzer aktualisieren
     */
    public static function update($id, $email, $isAdmin)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE users_users SET email = ?, is_admin = ? WHERE id = ?");
        $stmt->execute([$email, $isAdmin, $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Nutzer löschen
     */
    public static function delete($id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM users_users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * TOTP-Secret für einen Nutzer speichern
     */
    public static function updateTotpSecret($id, $secret)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE users_users SET totp_secret = ? WHERE id = ?");
        $stmt->execute([$secret, $id]);
        return $stmt->rowCount() > 0;
    }
}
