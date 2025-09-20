<?php
require_once __DIR__ . '/../../config/db.php';
class Categoria {
    public static function getAll() {
        global $conn;
        $sql = "SELECT * FROM Categoria";
        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getById($id) {
        global $conn;
        $sql = "SELECT * FROM Categoria WHERE id_categ = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public static function getSubcategorias($id) {
        global $conn;
        $sql = "SELECT * FROM Subcategoria WHERE id_categ = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function getAlertas($id) {
        global $conn;
        $sql = "SELECT a.* FROM Alertas a JOIN Subcategoria s ON a.id_prod = s.id_subcg WHERE s.id_categ = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>