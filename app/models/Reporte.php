<?php
require_once __DIR__ . '/../../config/db.php';
class Reporte {
    public static function getAll() {
        global $conn;
        $sql = "SELECT * FROM Reportes";
        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getByProducto($id) {
        global $conn;
        $sql = "SELECT * FROM Reportes WHERE id_prod = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getByProveedor($id) {
        global $conn;
        $sql = "SELECT * FROM Reportes WHERE id_nit = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getById($id) {
        global $conn;
        $sql = "SELECT * FROM Reportes WHERE id_repor = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
?>