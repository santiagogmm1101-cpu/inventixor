<?php
require_once __DIR__ . '/../../config/db.php';
class Alerta {
    public static function getAll() {
        global $conn;
        $sql = "SELECT * FROM Alertas";
        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getByCategoria($id) {
        global $conn;
        $sql = "SELECT a.* FROM Alertas a JOIN Productos p ON a.id_prod = p.id_prod JOIN Subcategoria s ON p.id_subcg = s.id_subcg WHERE s.id_categ = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getBySubcategoria($id) {
        global $conn;
        $sql = "SELECT a.* FROM Alertas a JOIN Productos p ON a.id_prod = p.id_prod WHERE p.id_subcg = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getByProducto($id) {
        global $conn;
        $sql = "SELECT * FROM Alertas WHERE id_prod = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getByProveedor($id) {
        global $conn;
        $sql = "SELECT a.* FROM Alertas a JOIN Productos p ON a.id_prod = p.id_prod WHERE p.id_nit = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getById($id) {
        global $conn;
        $sql = "SELECT * FROM Alertas WHERE id_alerta = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
?>