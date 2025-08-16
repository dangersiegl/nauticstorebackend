<?php
/**
 * src/Controllers/OrderController.php
 */

use App\Models\OrderModel;

class OrderController
{
    public function checkout()
    {
        // Nur eingeloggt
        $this->requireLogin();

        // Beispiel: Warenkorb aus Session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Hier nur als Beispiel:
        // $cart = $_SESSION['cart'] = [
        //     ['product_id' => 1, 'quantity' => 2, 'price' => 9.99],
        //     ['product_id' => 3, 'quantity' => 1, 'price' => 49.90]
        // ];

        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            echo "Warenkorb ist leer.";
            return;
        }

        // Bestellung anlegen
        $orderId = OrderModel::createOrder($_SESSION['user_id'], $cart);

        // Warenkorb leeren
        $_SESSION['cart'] = [];

        echo "Bestellung erfolgreich! Deine Bestellnummer: " . $orderId;
        // Hier könnte man auf eine Bestellbestätigungsseite oder so umleiten
    }

    public function list()
    {
        // Nur Admin
        $this->requireAdmin();

        $orders = OrderModel::getAllOrders();
        require_once __DIR__ . '/../Views/order/list.php';
    }

    public function view()
    {
        $this->requireAdmin();

        $id = $_GET['id'] ?? null;
        if (!$id) {
            die('Keine Bestell-ID angegeben.');
        }

        $order = OrderModel::getOrderWithItems($id);
        if (!$order) {
            die('Bestellung nicht gefunden.');
        }

        require_once __DIR__ . '/../Views/order/view.php';
    }

    public function updateStatus()
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderId = $_POST['order_id'] ?? null;
            $status = $_POST['status'] ?? 'pending';

            if ($orderId) {
                OrderModel::updateStatus($orderId, $status);
            }
        }
        header('Location: ?controller=order&action=list');
    }

    private function requireLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            die('Du musst eingeloggt sein, um zu bestellen.');
        }
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
