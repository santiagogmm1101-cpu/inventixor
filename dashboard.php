<?php
// dashboard.php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$user = $_SESSION['user'];
?>
<?php
require_once 'app/models/Producto.php';
require_once 'app/models/Proveedor.php';
require_once 'app/models/Salida.php';
require_once 'app/models/Alerta.php';
require_once 'app/models/Categoria.php';
require_once 'app/models/Subcategoria.php';
$categorias_count = count(Categoria::getAll());
$subcategorias_count = count(Subcategoria::getAll());
$productos_count = count(Producto::getAll());
$proveedores_count = count(Proveedor::getAll());
$salidas_count = count(Salida::getAll());
$alertas_count = count(Alerta::getAll());
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventixor</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="public/css/style.css">
    
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .sidebar-menu {
            padding: 0;
            margin: 0;
            list-style: none;
        }
        
        .menu-item {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .menu-link {
            display: block;
            padding: 1rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .menu-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            padding-left: 2rem;
        }
        
        .menu-link.active {
            background: rgba(255,255,255,0.2);
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary-color);
            transition: all 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .welcome-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-boxes"></i> Inventixor</h3>
            <p class="mb-0">Sistema de Inventario</p>
        </div>
        
        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="dashboard.php" class="menu-link active">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            <li class="menu-item">
                <a href="productos.php" class="menu-link">
                    <i class="fas fa-box me-2"></i> Productos
                </a>
            </li>
            <li class="menu-item">
                <a href="categorias.php" class="menu-link">
                    <i class="fas fa-tags me-2"></i> Categorías
                </a>
            </li>
            <li class="menu-item">
                <a href="subcategorias.php" class="menu-link">
                    <i class="fas fa-tag me-2"></i> Subcategorías
                </a>
            </li>
            <li class="menu-item">
                <a href="proveedores.php" class="menu-link">
                    <i class="fas fa-truck me-2"></i> Proveedores
                </a>
            </li>
            <li class="menu-item">
                <a href="salidas.php" class="menu-link">
                    <i class="fas fa-sign-out-alt me-2"></i> Salidas
                </a>
            </li>
            <li class="menu-item">
                <a href="reportes.php" class="menu-link">
                    <i class="fas fa-chart-bar me-2"></i> Reportes
                </a>
            </li>
            <li class="menu-item">
                <a href="alertas.php" class="menu-link">
                    <i class="fas fa-exclamation-triangle me-2"></i> Alertas
                </a>
            </li>
            <li class="menu-item">
                <a href="usuarios.php" class="menu-link">
                    <i class="fas fa-users me-2"></i> Usuarios
                </a>
            </li>
            <li class="menu-item">
                <a href="ia_ayuda.php" class="menu-link">
                    <i class="fas fa-robot me-2"></i> Asistente IA
                </a>
            </li>
            <li class="menu-item">
                <a href="logout.php" class="menu-link">
                    <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header de bienvenida -->
        <div class="welcome-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
                    <p class="mb-0">Bienvenido, <strong><?php
if (is_array($user) && isset($user['nombres'])) {
    echo htmlspecialchars($user['nombres']);
} else {
    echo 'Usuario';
}
?></strong> | Rol: <span class="badge bg-light text-dark"><?php
if (is_array($user) && isset($user['rol'])) {
    echo htmlspecialchars($user['rol']);
} else {
    echo 'N/A';
}
?></span></p>
                </div>
                <a href="logout.php" class="btn btn-light">
                    <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                </a>
            </div>
        </div>
        
        <!-- Estadísticas Generales -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Productos</h6>
                            <div class="stats-number"><?php echo $productos_count; ?></div>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-box fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Categorías</h6>
                            <div class="stats-number"><?php echo $categorias_count; ?></div>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-tags fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Proveedores</h6>
                            <div class="stats-number"><?php echo $proveedores_count; ?></div>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-truck fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Accesos Rápidos -->
        <h4 class="mb-3"><i class="fas fa-rocket me-2"></i>Accesos Rápidos</h4>
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <i class="fas fa-box text-primary fa-2x mb-3"></i>
                    <h6>Gestión de Productos</h6>
                    <a href="productos.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>Ir
                    </a>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <i class="fas fa-tags text-success fa-2x mb-3"></i>
                    <h6>Gestión de Categorías</h6>
                    <a href="categorias.php" class="btn btn-success btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>Ir
                    </a>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <i class="fas fa-truck text-info fa-2x mb-3"></i>
                    <h6>Gestión de Proveedores</h6>
                    <a href="proveedores.php" class="btn btn-info btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>Ir
                    </a>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <i class="fas fa-sign-out-alt text-warning fa-2x mb-3"></i>
                    <h6>Gestión de Salidas</h6>
                    <a href="salidas.php" class="btn btn-warning btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>Ir
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Más estadísticas -->
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Subcategorías</h6>
                            <div class="stats-number"><?php echo $subcategorias_count; ?></div>
                        </div>
                        <div class="text-secondary">
                            <i class="fas fa-tag fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Salidas</h6>
                            <div class="stats-number"><?php echo $salidas_count; ?></div>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-sign-out-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Alertas</h6>
                            <div class="stats-number"><?php echo $alertas_count; ?></div>
                        </div>
                        <div class="text-danger">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Módulos</h6>
                            <div class="stats-number">8</div>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-th-large fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript personalizado -->
    <script>
        // Animaciones al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const statsCards = document.querySelectorAll('.stats-card');
            statsCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
        
        // Actualizar datos en tiempo real (opcional)
        setInterval(function() {
            // Aquí se puede agregar código para actualizar las estadísticas automáticamente
            console.log('Actualizando datos...');
        }, 30000); // Cada 30 segundos
    </script>
</body>
</html>