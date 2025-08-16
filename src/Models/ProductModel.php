<?php
/**
 * src/Models/ProductModel.php
 */

namespace App\Models;

class ProductModel
{
    public static function create($name, $description, $price)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price) VALUES (?, ?, ?)");
        return $stmt->execute([$name, $description, $price]);
    }

    public static function update($id, $name, $description, $price)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=? WHERE id=?");
        return $stmt->execute([$name, $description, $price, $id]);
    }

    public static function delete($id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function getAll()
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public static function getById($id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
