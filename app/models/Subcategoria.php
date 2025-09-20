<?php
require_once __DIR__ . '/../../config/db.php';
class Subcategoria {
    public static function getAll() {
        global $conn;
        $sql = "SELECT * FROM Subcategoria";
        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getByCategoria($id) {
        global $conn;
        $sql = "SELECT * FROM Subcategoria WHERE id_categ = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getById($id) {
        global $conn;
        $sql = "SELECT * FROM Subcategoria WHERE id_subcg = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public static function getProductos($id) {
        global $conn;
        $sql = "SELECT * FROM Productos WHERE id_subcg = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getAlertas($id) {
        global $conn;
        $sql = "SELECT a.* FROM Alertas a JOIN Productos p ON a.id_prod = p.id_prod WHERE p.id_subcg = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>