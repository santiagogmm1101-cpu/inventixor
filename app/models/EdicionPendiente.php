<?php
require_once __DIR__ . '/../../config/db.php';
class EdicionPendiente {
    public static function crear($id_prod, $datos, $usuario) {
        global $conn;
        $sql = "INSERT INTO EdicionesPendientes (id_prod, datos_json, usuario_solicita, estado, fecha_solicitud) VALUES (?, ?, ?, 'pendiente', NOW())";
        $stmt = $conn->prepare($sql);
        $json = json_encode($datos);
        $stmt->bind_param('iss', $id_prod, $json, $usuario);
        $stmt->execute();
        $stmt->close();
    }
    public static function aprobar($id_edicion) {
        global $conn;
        $sql = "UPDATE EdicionesPendientes SET estado='aprobada', fecha_aprobacion=NOW() WHERE id_edicion=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id_edicion);
        $stmt->execute();
        $stmt->close();
    }
    public static function rechazar($id_edicion) {
        global $conn;
        $sql = "UPDATE EdicionesPendientes SET estado='rechazada', fecha_aprobacion=NOW() WHERE id_edicion=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id_edicion);
        $stmt->execute();
        $stmt->close();
    }
    public static function getPendientes() {
        global $conn;
        $sql = "SELECT * FROM EdicionesPendientes WHERE estado='pendiente'";
        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
