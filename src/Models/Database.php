<?php
/**
 * src/Models/Database.php
 */

class Database
{
    private static $pdo = null;

    public static function getConnection()
    {
        if (self::$pdo === null) {
            try {
                // Lade die Konfiguration
                require_once __DIR__ . '/../../config/config.php';

                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                die('Datenbank-Verbindungsfehler: ' . $e->getMessage());
            }
        }
        return self::$pdo;
    }

    /**
     * Führt eine Abfrage aus
     *
     * @param string $sql
     * @param array $params
     * @return PDOStatement|false
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            die('Datenbank-Abfragefehler: ' . $e->getMessage());
        }
    }
}
