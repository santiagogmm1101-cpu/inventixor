<?php
require_once __DIR__ . '/../../config/db.php';
class Salida {
    public static function getAll() {
        global $conn;
        $sql = "SELECT * FROM Salidas";
        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getByProducto($id) {
        global $conn;
        $sql = "SELECT * FROM Salidas WHERE id_prod = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getById($id) {
        global $conn;
        $sql = "SELECT * FROM Salidas WHERE id_salida = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public static function getProductos($id) {
        global $conn;
        $sql = "SELECT p.* FROM Productos p JOIN Salidas s ON p.id_prod = s.id_prod WHERE s.id_salida = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>