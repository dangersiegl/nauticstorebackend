<?php

class UserlistController
{
    public function neu()
    {
        // Hier das Formular anzeigen
        require_once __DIR__ . '/../Views/user/neu.php';
    }

    public function store()
    {
        // Hier das Formular auswerten und User in DB anlegen
        // ...
    }
}
