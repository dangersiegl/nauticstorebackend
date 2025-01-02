<?php
// src/Controllers/BaseController.php

/**
 * Basisklasse für alle Controller.
 * Stellt allgemeine Hilfsmethoden bereit.
 */
class BaseController
{
    /**
     * Stellt sicher, dass ein Benutzer eingeloggt ist.
     * Leitet den Benutzer zur Login-Seite um, wenn er nicht eingeloggt ist.
     *
     * @param int|null $userId Die ID des eingeloggten Benutzers (optional).
     *                         Wenn nicht angegeben, wird $_SESSION['user_id'] verwendet.
     */
    protected function requireLogin($userId = null)
    {
        $userId = $userId ?? $_SESSION['user_id'] ?? null; // Verwende übergebene ID oder die aus der Session

        if (empty($userId)) {
            header('Location: /login');
            exit;
        }
    }
}
?>