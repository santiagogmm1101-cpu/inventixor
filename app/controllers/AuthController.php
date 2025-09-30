<?php
// app/controllers/AuthController.php
require_once 'app/models/User.php';

class AuthController {
    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = new User();
            $login = $user->login($_POST['username'], $_POST['password']);
            if ($login) {
                // Guardar solo nombre y rol en sesión
                $_SESSION['user'] = [
                    'nombres' => $login['nombres'],
                    'rol' => $login['rol']
                ];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        }
        include 'app/views/login.php';
    }
}
?>