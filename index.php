<?php
/**
 * Punto de entrada del sistema Inventixor
 * Página de inicio de sesión
 */

session_start();
require_once 'config/db.php';
require_once 'app/helpers/Database.php';

// Si ya está autenticado, redirigir al dashboard
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$db = new Database();
$error = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    // Intentar login por rol, correo o número de documento
    $sql = "SELECT * FROM Users WHERE LOWER(rol) = ? OR LOWER(correo) = ? OR num_doc = ?";
    $stmt = $db->conn->prepare($sql);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventixor - Sistema de Inventario</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS personalizado -->
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-form {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .btn-login {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .credentials-info {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-container">
                    <!-- Header -->
                    <div class="login-header">
                        <div class="logo">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <h2 class="mb-0">Inventixor</h2>
                        <p class="mb-0">Sistema de Gestión de Inventario</p>
                    </div>
                    
                    <!-- Formulario -->
                    <div class="login-form">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="usuario" name="usuario" 
                                       placeholder="Usuario" required>
                                <label for="usuario">
                                    <i class="fas fa-user"></i> Usuario
                                </label>
                            </div>
                            
                            <div class="form-floating">
                                <input type="password" class="form-control" id="contrasena" name="contrasena" 
                                       placeholder="Contraseña" required>
                                <label for="contrasena">
                                    <i class="fas fa-lock"></i> Contraseña
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-login">
                                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Información del sistema -->
                <div class="text-center mt-3 text-white">
                    <small>
                        <i class="fas fa-copyright"></i> 2025 Inventixor v1.0 
                        | Sistema de Gestión de Inventario
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript personalizado -->
    <script>
        // Animación al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const loginContainer = document.querySelector('.login-container');
            loginContainer.style.opacity = '0';
            loginContainer.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                loginContainer.style.transition = 'all 0.6s ease';
                loginContainer.style.opacity = '1';
                loginContainer.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>