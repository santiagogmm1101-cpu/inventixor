<?php
// dashboard.php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventixor - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Menú lateral -->
        <nav class="col-md-2 d-none d-md-block bg-dark sidebar vh-100">
            <div class="sidebar-sticky pt-3">
                <h4 class="text-white text-center mb-4">Menú</h4>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link text-white" href="#">Productos</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="#">Categorías</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="#">Subcategorías</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="#">Proveedores</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="#">Salidas</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="#">Reportes</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="#">Alertas</a></li>
                </ul>
            </div>
        </nav>
        <!-- Contenido principal -->
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Bienvenido, <?php echo htmlspecialchars($user['nombres']); ?> (<?php echo htmlspecialchars($user['rol']); ?>)</h2>
                <a href="logout.php" class="btn btn-danger">Cerrar sesión</a>
            </div>
            <div class="row mb-4">
                <!-- Accesos rápidos -->
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Gestión de Productos</h5>
                            <a href="#" class="btn btn-primary">Ir</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Gestión de Proveedores</h5>
                            <a href="#" class="btn btn-primary">Ir</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Gestión de Salidas</h5>
                            <a href="#" class="btn btn-primary">Ir</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Gestión de Reportes</h5>
                            <a href="#" class="btn btn-primary">Ir</a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Estadísticas -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card text-center bg-info text-white mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Productos</h5>
                            <p class="card-text display-6">0</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-success text-white mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Proveedores</h5>
                            <p class="card-text display-6">0</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-warning text-white mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Salidas</h5>
                            <p class="card-text display-6">0</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-danger text-white mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Alertas</h5>
                            <p class="card-text display-6">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>