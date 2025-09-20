<?php
require_once __DIR__ . '/../../config/db.php';
class Producto {
    public static function getAll() {
        global $conn;
        $sql = "SELECT * FROM Productos";
        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getBySubcategoria($id) {
        global $conn;
        $sql = "SELECT * FROM Productos WHERE id_subcg = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getById($id) {
        global $conn;
        $sql = "SELECT * FROM Productos WHERE id_prod = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public static function getProveedores($id) {
        global $conn;
        $sql = "SELECT pr.* FROM Proveedores pr JOIN Productos p ON pr.id_nit = p.id_nit WHERE p.id_prod = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getSalidas($id) {
        global $conn;
        $sql = "SELECT * FROM Salidas WHERE id_prod = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getAlertas($id) {
        global $conn;
        $sql = "SELECT * FROM Alertas WHERE id_prod = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getReportes($id) {
        global $conn;
        $sql = "SELECT * FROM Reportes WHERE id_prod = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>