<?php
/**
 * src/Controllers/OrderController.php
 */

use App\Models\OrderModel;
use App\Models\ProductModel; // neu importieren

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

    public function neu()
    {
        // Nur Admins dürfen neue Bestellungen anlegen
        $this->requireAdmin();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Einfacher POST-Handler: aktueller User (Admin) legt Bestellung mit einem Produkt an
            $userId    = $_SESSION['user_id'] ?? null;
            $productId = $_POST['product_id'] ?? null;
            $quantity  = max(1, (int)($_POST['quantity'] ?? 1));

            if (!$userId || !$productId) {
                $error = "Bitte ein Produkt auswählen.";
            } else {
                // Produktdaten holen (falls vorhanden) — Price optional
                $product = ProductModel::getById($productId);
                $price = $product['price'] ?? 0;

                $items = [
                    ['product_id' => $productId, 'quantity' => $quantity, 'price' => $price]
                ];

                $orderId = OrderModel::createOrder($userId, $items);

                header('Location: ?controller=order&action=view&id=' . urlencode($orderId));
                exit;
            }
        }

        // Produkte für das Dropdown holen
        $products = ProductModel::getAll();

        // Falls es eine dedicated View gibt, diese verwenden
        $viewFile = __DIR__ . '/../Views/order/create.php';
        if (file_exists($viewFile)) {
            require_once $viewFile;
            return;
        }

        // Fallback: einfache Form inline rendern (verwenden Sie besser eine View-Datei)
        require __DIR__ . '/../Views/partials/header.php';
        ?>
        <div class="content-box">
            <h2>Neue Bestellung anlegen</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" action="?controller=order&action=neu" class="admin-form">
                <div class="form-group">
                    <label for="product_id">Produkt</label>
                    <select name="product_id" id="product_id" required>
                        <?php foreach ($products as $p): 
                            $label = $p['name_de'] ?? $p['name_en'] ?? '(kein Name)';
                        ?>
                            <option value="<?php echo htmlspecialchars($p['id']); ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity">Menge</label>
                    <input type="number" name="quantity" id="quantity" value="1" min="1">
                </div>

                <button type="submit" class="btn btn-primary">Bestellung anlegen</button>
            </form>
        </div>
        <?php
        require __DIR__ . '/../Views/partials/footer.php';
    }

    private function requireLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            die('Sie müssen eingeloggt sein, um zu bestellen.');
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
