<?php
require_once __DIR__ . '/../../config/db.php';
class Alerta {
    /**
     * Registra una alerta en la tabla Alertas
     * @param array $data
     * Campos requeridos: tipo_alerta, observacion, nivel_alerta, estado, id_prod
     */
    public static function registrarAlerta($data) {
        require_once __DIR__ . '/../helpers/Database.php';
        $db = new Database();
        $conn = $db->conn;
        $tipo_alerta = $conn->real_escape_string($data['tipo_alerta']);
        $observacion = $conn->real_escape_string($data['observacion']);
        $nivel_alerta = $conn->real_escape_string($data['nivel_alerta']);
        $estado = $conn->real_escape_string($data['estado']);
        $id_prod = intval($data['id_prod']);
        $fecha_generacion = date('Y-m-d');
        $sql = "INSERT INTO Alertas (tipo_alerta, observacion, nivel_alerta, fecha_generacion, estado, id_prod) VALUES (?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sssssi', $tipo_alerta, $observacion, $nivel_alerta, $fecha_generacion, $estado, $id_prod);
            $stmt->execute();
            $stmt->close();
        } else {
            error_log('Error al preparar el statement para registrar alerta: ' . $conn->error);
        }
    }
    public static function registrarRetorno($data) {
        global $conn;
        $id_prod = intval($data['id_prod']);
        $motivo = $conn->real_escape_string($data['motivo']);
        $usuario = $conn->real_escape_string($data['usuario']);
        $tipo = 'retorno';
        $destinatarios = json_encode($data['destinatarios']);
        $sql = "INSERT INTO Alertas (id_prod, motivo, usuario, tipo, destinatarios, fecha) VALUES ($id_prod, '$motivo', '$usuario', '$tipo', '$destinatarios', NOW())";
        $conn->query($sql);
    }
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