<?php
/**
 * src/Controllers/ProductController.php
 */

require_once __DIR__ . '/../Models/ProductModel.php';

class ProductController
{
    // Liste aller Produkte anzeigen
    public function list()
    {
        $products = ProductModel::getAll();
        require_once __DIR__ . '/../Views/product/list.php';
    }

    // Produkt anlegen (nur Admin)
    public function create()
    {
        // Nur wenn Admin eingeloggt
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;

            ProductModel::create($name, $description, $price);
            header('Location: ?controller=product&action=list');
            exit;
        }

        require_once __DIR__ . '/../Views/product/create.php';
    }

    // Produkt bearbeiten
    public function edit()
    {
        $this->requireAdmin();

        $id = $_GET['id'] ?? null;
        if (!$id) {
            die('Kein Produkt angegeben.');
        }

        $product = ProductModel::getById($id);
        if (!$product) {
            die('Produkt nicht gefunden.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;

            ProductModel::update($id, $name, $description, $price);
            header('Location: ?controller=product&action=list');
            exit;
        }

        require_once __DIR__ . '/../Views/product/edit.php';
    }

    // Produkt löschen
    public function delete()
    {
        $this->requireAdmin();

        $id = $_GET['id'] ?? null;
        if ($id) {
            ProductModel::delete($id);
        }
        header('Location: ?controller=product&action=list');
    }

    // Nur Admins dürfen hierhin
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
