<?php
// src/Controllers/ProductController.php

use App\Models\ProductModel;

class ProductController
{
    // Formular anzeigen
    public function create()
    {
        $this->requireAdmin();

        // Nur anzeigen (kein POST hier)
        require_once __DIR__ . '/../Views/product/create.php';
    }

    // POST verarbeiten und Produkt in DB speichern
    public function store()
    {
        $this->requireAdmin();

        // 1) Formulardaten
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;

        // 2) Validierung
        if (empty($name) || empty($price)) {
            $error = "Bitte fülle die Pflichtfelder (Name, Preis) aus!";
            // Neu laden:
            require_once __DIR__ . '/../Views/product/create.php';
            return;
        }

        // 3) DB-Aufruf
        ProductModel::create($name, $description, $price);

        // 4) Weiterleitung zur Liste
        // Du kannst /artikel oder /product/list nehmen, je nach Route
        header('Location: /artikel');
        exit;
    }

    public function list()
    {
        $this->requireAdmin();
        $products = ProductModel::getAll();
        require_once __DIR__ . '/../Views/product/list.php';
    }

    // Produkt bearbeiten
    public function edit()
    {
        
    }

    // Produkt löschen
    public function delete()
    {
        
    }

    private function requireAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] == 0) {
            die('Zugriff verweigert: Keine Admin-Rechte.');
        }
    }
}
