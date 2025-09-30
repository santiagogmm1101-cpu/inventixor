<?php
require_once __DIR__ . '/../../config/db.php';
class Salida {
    public static function getAll() {
        global $conn;
        $sql = "SELECT s.*, p.nombre as producto FROM Salidas s 
                LEFT JOIN Productos p ON s.id_prod = p.id_prod 
                ORDER BY s.fecha_hora DESC";
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
    public static function registrarSalida($producto_id, $cantidad) {
        global $conn;
        // Insertar salida
        $stmt = $conn->prepare("INSERT INTO Salidas (id_prod, cantidad, fecha_hora) VALUES (?, ?, NOW())");
        $stmt->bind_param('ii', $producto_id, $cantidad);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) {
            // Actualizar stock
            $stmt2 = $conn->prepare("UPDATE Productos SET stock = stock - ? WHERE id_prod = ?");
            $stmt2->bind_param('ii', $cantidad, $producto_id);
            $stmt2->execute();
            $stmt2->close();
            return true;
        }
        return false;
    }
    public static function eliminarSalida($id_salida) {
        global $conn;
        // Obtener datos de la salida
        $stmt = $conn->prepare("SELECT id_prod, cantidad FROM Salidas WHERE id_salida = ?");
        $stmt->bind_param('i', $id_salida);
        $stmt->execute();
        $res = $stmt->get_result();
        $salida = $res->fetch_assoc();
        $stmt->close();
        if (!$salida) return false;
        // Eliminar salida
        $stmt2 = $conn->prepare("DELETE FROM Salidas WHERE id_salida = ?");
        $stmt2->bind_param('i', $id_salida);
        $ok = $stmt2->execute();
        $stmt2->close();
        if ($ok) {
            // Restaurar stock
            $stmt3 = $conn->prepare("UPDATE Productos SET stock = stock + ? WHERE id_prod = ?");
            $stmt3->bind_param('ii', $salida['cantidad'], $salida['id_prod']);
            $stmt3->execute();
            $stmt3->close();
            return true;
        }
        return false;
    }
}
?>