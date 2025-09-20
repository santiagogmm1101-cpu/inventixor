<?php
require_once __DIR__ . '/../../config/db.php';
class Proveedor {
    public static function getAll() {
        global $conn;
        $sql = "SELECT * FROM Proveedores";
        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getByProducto($id) {
        global $conn;
        $sql = "SELECT pr.* FROM Proveedores pr JOIN Productos p ON pr.id_nit = p.id_nit WHERE p.id_prod = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getById($id) {
        global $conn;
        $sql = "SELECT * FROM Proveedores WHERE id_nit = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public static function getAlertas($id) {
        global $conn;
        $sql = "SELECT a.* FROM Alertas a JOIN Productos p ON a.id_prod = p.id_prod WHERE p.id_nit = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getReportes($id) {
        global $conn;
        $sql = "SELECT * FROM Reportes WHERE id_nit = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>