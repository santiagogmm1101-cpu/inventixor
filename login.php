<?php
session_start();
require_once 'app/helpers/Database.php';

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['usuario'] ?? $_POST['num_doc'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    // Intentar login por rol, correo o número de documento
    $sql = "SELECT * FROM Users WHERE LOWER(rol) = ? OR LOWER(correo) = ? OR num_doc = ?";
    $stmt = $db->conn->prepare("SELECT * FROM Users WHERE LOWER(rol) = ? OR LOWER(correo) = ? OR num_doc = ?");
    $username_lower = strtolower(trim($username));
    $num_doc = is_numeric($username) ? intval($username) : 0;
    $stmt->bind_param("ssi", $username_lower, $username_lower, $num_doc);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    if (!$usuario) {
        $error = "Usuario no encontrado: " . $username;
    } elseif (!password_verify($contrasena, $usuario['contrasena'])) {
        $error = "Contraseña incorrecta";
    } else {
        // Login exitoso
        $_SESSION['user'] = $usuario;
        $_SESSION['rol'] = $usuario['rol']; // Agregar rol explícitamente
        header('Location: dashboard.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Inventixor</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Ingresar al sistema</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="usuario">Usuario (rol, correo o número de documento):</label>
                <input type="text" name="usuario" id="usuario" class="form-control" placeholder="admin, coordinador, auxiliar, correo o documento" required>
            </div>
            <div class="form-group">
                <label for="contrasena">Contraseña:</label>
                <input type="password" name="contrasena" id="contrasena" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Ingresar</button>
        </form>
        
        <div class="mt-3">
            <small class="text-muted">
                <strong>Usuarios por defecto:</strong><br>
                admin / admin123<br>
                coordinador / c123<br>
                auxiliar / a123
            </small>
        </div>
    </div>
</body>
</html>
