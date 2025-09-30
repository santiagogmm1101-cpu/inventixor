<?php
// app/models/User.php
require_once __DIR__ . '/../../config/db.php';

class User {
    public function login($username, $password) {
        global $conn;
        
        // Intentar login por rol (admin, coordinador, auxiliar) o por correo
        $sql = "SELECT nombres, rol, contrasena FROM Users WHERE LOWER(rol) = ? OR LOWER(correo) = ?";
        $stmt = $conn->prepare($sql);
        $username_lower = strtolower(trim($username));
        $stmt->bind_param('ss', $username_lower, $username_lower);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['contrasena']) || $password === $row['contrasena']) {
                // Solo devolver nombre y rol
                return [
                    'nombres' => $row['nombres'],
                    'rol' => $row['rol']
                ];
            }
        }
        return false;
    }
}
?>