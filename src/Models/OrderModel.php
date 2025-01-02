<?php
/**
 * src/Models/OrderModel.php
 */

require_once __DIR__ . '/Database.php';

class OrderModel
{
    /**
     * Neue Bestellung anlegen
     */
    public static function createOrder($userId, $items)
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            // Gesamtsumme berechnen
            $totalPrice = 0;
            foreach ($items as $item) {
                $totalPrice += $item['price'] * $item['quantity'];
            }

            // Order erstellen
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$userId, $totalPrice]);
            $orderId = $pdo->lastInsertId();

            // OrderItems anlegen
            $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($items as $item) {
                $stmtItem->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
            }

            $pdo->commit();
            return $orderId;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e; // In echtem Code: Besseres Error-Handling
        }
    }

    /**
     * Alle Bestellungen (nur für Admin)
     */
    public static function getAllOrders()
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT o.*, u.email AS user_email 
                             FROM orders o
                             JOIN users u ON o.user_id = u.id
                             ORDER BY o.created_at DESC");
        return $stmt->fetchAll();
    }

    /**
     * Einzelne Bestellung mit Items
     */
    public static function getOrderWithItems($orderId)
    {
        $pdo = Database::getConnection();
        // Order
        $stmt = $pdo->prepare("SELECT o.*, u.email AS user_email 
                               FROM orders o
                               JOIN users u ON o.user_id = u.id
                               WHERE o.id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if ($order) {
            // Items
            $stmtItems = $pdo->prepare("SELECT oi.*, p.name 
                                        FROM order_items oi
                                        JOIN products p ON oi.product_id = p.id
                                        WHERE oi.order_id = ?");
            $stmtItems->execute([$orderId]);
            $order['items'] = $stmtItems->fetchAll();
        }

        return $order;
    }

    /**
     * Bestellstatus aktualisieren
     */
    public static function updateStatus($orderId, $status)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $orderId]);
    }
}
