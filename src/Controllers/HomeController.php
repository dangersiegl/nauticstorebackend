<?php

// src/Controllers/HomeController.php

require_once __DIR__ . '/BaseController.php';



class HomeController extends BaseController

{

    public function index()

    {

        $this->requireLogin(); // Nur eingeloggte User



        // Wenn wir hier ankommen, ist der User eingeloggt.

        // => Dashboard-Inhalt

        require __DIR__ . '/../Views/dashboard.php';

    }

}



?>