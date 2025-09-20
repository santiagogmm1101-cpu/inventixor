<?php
// app/models/User.php
require_once __DIR__ . '/../../config/db.php';

class User {
    public function login($username, $password) {
        global $conn;
    $sql = "SELECT * FROM Users WHERE LOWER(correo) = ?";
    $stmt = $conn->prepare($sql);
    $correo = strtolower(trim($username));
    $stmt->bind_param('s', $correo);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            // Si la contraseña está encriptada, usar password_verify
            if (password_verify($password, $row['contrasena']) || $password === $row['contrasena']) {
                return $row;
            }
        }
        return false;
    }
}
?>