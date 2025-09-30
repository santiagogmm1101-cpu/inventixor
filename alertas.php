<?php
require_once 'app/helpers/Database.php';
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

// Inicializar $_SESSION['rol'] si no existe
if (!isset($_SESSION['rol'])) {
    if (isset($_SESSION['user']['rol'])) {
        $_SESSION['rol'] = $_SESSION['user']['rol'];
    } else {
        $_SESSION['rol'] = '';
    }
}

$db = new Database();

// Manejar acciones AJAX
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'crear':
            $tipo_alerta = $_POST['tipo_alerta'];
            $observacion = $_POST['observacion'];
            $nivel_alerta = $_POST['nivel_alerta'];
            $fecha_generacion = date('Y-m-d');
            $estado = $_POST['estado'] ?? 'Activa';
            $id_prod = isset($_POST['id_prod']) && !empty($_POST['id_prod']) ? intval($_POST['id_prod']) : null;

            
            if ($id_prod) {
                $stmt = $db->conn->prepare("INSERT INTO Alertas (tipo_alerta, observacion, nivel_alerta, fecha_generacion, estado, id_prod) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssssi', $tipo_alerta, $observacion, $nivel_alerta, $fecha_generacion, $estado, $id_prod);
            } else {
                $stmt = $db->conn->prepare("INSERT INTO Alertas (tipo_alerta, observacion, nivel_alerta, fecha_generacion, estado) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('sssss', $tipo_alerta, $observacion, $nivel_alerta, $fecha_generacion, $estado);
            }
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Alerta creada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear la alerta']);
            }
            $stmt->close();
            exit;
            
        case 'editar':
            $id_alerta = intval($_POST['id_alerta']);
            $tipo_alerta = $_POST['tipo_alerta'];
            $observacion = $_POST['observacion'];
            $nivel_alerta = $_POST['nivel_alerta'];
            $estado = $_POST['estado'];
            $id_prod = isset($_POST['id_prod']) && !empty($_POST['id_prod']) ? intval($_POST['id_prod']) : null;
            
            if ($id_prod) {
                $stmt = $db->conn->prepare("UPDATE Alertas SET tipo_alerta=?, observacion=?, nivel_alerta=?, estado=?, id_prod=? WHERE id_alerta=?");
                $stmt->bind_param('ssssii', $tipo_alerta, $observacion, $nivel_alerta, $estado, $id_prod, $id_alerta);
            } else {
                $stmt = $db->conn->prepare("UPDATE Alertas SET tipo_alerta=?, observacion=?, nivel_alerta=?, estado=?, id_prod=NULL WHERE id_alerta=?");
                $stmt->bind_param('ssssi', $tipo_alerta, $observacion, $nivel_alerta, $estado, $id_alerta);
            }
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Alerta actualizada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la alerta']);
            }
            $stmt->close();
            exit;
            
        case 'eliminar':
            $id_alerta = intval($_POST['id_alerta']);
            $stmt = $db->conn->prepare("DELETE FROM Alertas WHERE id_alerta=?");
            $stmt->bind_param('i', $id_alerta);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Alerta eliminada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar la alerta']);
            }
            $stmt->close();
            exit;
            
        case 'cambiar_estado':
            $id_alerta = intval($_POST['id_alerta']);
            $nuevo_estado = $_POST['nuevo_estado'];
            $stmt = $db->conn->prepare("UPDATE Alertas SET estado=? WHERE id_alerta=?");
            $stmt->bind_param('si', $nuevo_estado, $id_alerta);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Estado actualizado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
            }
            $stmt->close();
            exit;
    }
}

// Filtros
$filtro_tipo = isset($_GET['filtro_tipo']) ? $_GET['filtro_tipo'] : '';
$filtro_nivel = isset($_GET['filtro_nivel']) ? $_GET['filtro_nivel'] : '';
$filtro_estado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';
$filtro_producto = isset($_GET['filtro_producto']) ? $_GET['filtro_producto'] : '';

// Construir consulta con JOIN y filtros
$sql = "SELECT 
            a.id_alerta, 
            a.tipo_alerta, 
            a.observacion, 
            a.nivel_alerta, 
            a.fecha_generacion, 
            a.estado,
            p.nombre AS producto_nombre,
            p.stock AS producto_stock,
            sc.nombre AS subcategoria_nombre,
            c.nombre AS categoria_nombre,
            prov.razon_social AS proveedor_nombre,
            u.nombres AS usuario_nombres
        FROM Alertas a 
        LEFT JOIN Productos p ON a.id_prod = p.id_prod
        LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
        LEFT JOIN Categoria c ON sc.id_categ = c.id_categ
        LEFT JOIN Proveedores prov ON p.id_nit = prov.id_nit
        LEFT JOIN Users u ON p.num_doc = u.num_doc";

$where_conditions = [];
$params = [];
$types = '';

if (!empty($filtro_tipo)) {
    $where_conditions[] = "a.tipo_alerta LIKE ?";
    $params[] = "%$filtro_tipo%";
    $types .= 's';
}

if (!empty($filtro_nivel)) {
    $where_conditions[] = "a.nivel_alerta = ?";
    $params[] = $filtro_nivel;
    $types .= 's';
}

if (!empty($filtro_estado)) {
    $where_conditions[] = "a.estado = ?";
    $params[] = $filtro_estado;
    $types .= 's';
}

if (!empty($filtro_producto)) {
    $where_conditions[] = "p.nombre LIKE ?";
    $params[] = "%$filtro_producto%";
    $types .= 's';
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY 
    CASE a.nivel_alerta 
        WHEN 'Crítico' THEN 1 
        WHEN 'Alto' THEN 2 
        WHEN 'Medio' THEN 3 
        WHEN 'Bajo' THEN 4 
        ELSE 5 
    END, 
    a.fecha_generacion DESC";

if (!empty($params)) {
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $db->conn->query($sql);
}

// Obtener datos para los filtros
$productos = $db->conn->query("SELECT id_prod, nombre FROM Productos ORDER BY nombre");
// Los reportes no están directamente relacionados con alertas según el esquema

// Obtener estadísticas
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'Activa' THEN 1 ELSE 0 END) as activas,
                SUM(CASE WHEN estado = 'Resuelta' THEN 1 ELSE 0 END) as resueltas,
                SUM(CASE WHEN nivel_alerta = 'Crítico' THEN 1 ELSE 0 END) as criticas,
                SUM(CASE WHEN nivel_alerta = 'Alto' THEN 1 ELSE 0 END) as altas,
                SUM(CASE WHEN nivel_alerta = 'Medio' THEN 1 ELSE 0 END) as medias,
                SUM(CASE WHEN nivel_alerta = 'Bajo' THEN 1 ELSE 0 END) as bajas
              FROM Alertas";
$stats_result = $db->conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Alertas - Inventixor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: #bdc3c7 !important;
            transition: all 0.3s ease;
            margin: 2px 0;
            border-radius: 8px;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db !important;
            transform: translateX(5px);
        }
        
        .main-content {
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin: 20px;
            padding: 30px;
            min-height: calc(100vh - 40px);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 20px;
            color: white;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        
        .alert-card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 5px solid;
            margin-bottom: 15px;
        }
        
        .alert-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .alert-critico { border-left-color: #e74c3c; }
        .alert-alto { border-left-color: #f39c12; }
        .alert-medio { border-left-color: #f1c40f; }
        .alert-bajo { border-left-color: #27ae60; }
        
        .badge-nivel {
            font-size: 0.85em;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .badge-critico {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            animation: pulseRed 2s infinite;
        }
        
        .badge-alto {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }
        
        .badge-medio {
            background: linear-gradient(135deg, #f1c40f, #f39c12);
            color: #2c3e50;
        }
        
        .badge-bajo {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        
        .badge-estado {
            font-size: 0.8em;
            padding: 6px 10px;
            border-radius: 15px;
        }
        
        .estado-activa { background: #e74c3c; color: white; }
        .estado-resuelta { background: #27ae60; color: white; }
        .estado-pendiente { background: #f39c12; color: white; }
        
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
            color: white;
        }
        
        .filter-card {
            background: rgba(255,255,255,0.9);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
        }
        
        .table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.1);
            transform: scale(1.01);
        }
        
        .action-buttons .btn {
            margin: 2px;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.85em;
        }
        
        @keyframes pulseRed {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        .page-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 30px;
        }
        
        .search-container {
            position: relative;
        }
        
        .search-container .fa-search {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #667eea;
        }
        
        .search-container input {
            padding-left: 45px;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar Navigation -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar position-fixed h-100">
            <div class="position-sticky pt-4">
                <div class="text-center mb-4">
                    <h3 class="text-white fw-bold">
                        <i class="fas fa-cube me-2"></i>Inventixor
                    </h3>
                    <p class="text-light opacity-75 small">Sistema de Inventario</p>
                </div>
                
                <ul class="nav flex-column px-2">
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center py-2 px-3" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-3"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center py-2 px-3" href="productos.php">
                            <i class="fas fa-box me-3"></i>
                            <span>Productos</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center py-2 px-3" href="categorias.php">
                            <i class="fas fa-tags me-3"></i>
                            <span>Categorías</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center py-2 px-3" href="subcategorias.php">
                            <i class="fas fa-list me-3"></i>
                            <span>Subcategorías</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center py-2 px-3" href="proveedores.php">
                            <i class="fas fa-truck me-3"></i>
                            <span>Proveedores</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center py-2 px-3" href="salidas.php">
                            <i class="fas fa-sign-out-alt me-3"></i>
                            <span>Salidas</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center py-2 px-3" href="reportes.php">
                            <i class="fas fa-chart-bar me-3"></i>
                            <span>Reportes</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center py-2 px-3 active" href="alertas.php">
                            <i class="fas fa-bell me-3"></i>
                            <span>Alertas</span>
                        </a>
                    </li>
                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center py-2 px-3" href="usuarios.php">
                            <i class="fas fa-users me-3"></i>
                            <span>Usuarios</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center py-2 px-3" href="ia_ayuda.php">
                            <i class="fas fa-robot me-3"></i>
                            <span>Asistente IA</span>
                        </a>
                    </li>
                </ul>
                
                <div class="mt-auto pt-4 px-2">
                    <div class="bg-light bg-opacity-10 rounded p-3 mb-3">
                        <div class="d-flex align-items-center text-light">
                            <i class="fas fa-user-circle fs-4 me-2"></i>
                            <div>
                                <div class="fw-semibold"><?php echo $_SESSION['user']['nombres'] ?? 'Usuario'; ?></div>
                                <small class="opacity-75"><?php echo ucfirst($_SESSION['rol']); ?></small>
                            </div>
                        </div>
                    </div>
                    <a class="nav-link d-flex align-items-center py-2 px-3 text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt me-3"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </nav>
                            <i class="fas fa-users me-2"></i>Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ia_ayuda.php">
                            <i class="fas fa-robot me-2"></i>Asistente IA
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 col-lg-10 ms-md-auto">
            <div class="main-content fade-in">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="page-title">
                        <i class="fas fa-bell me-3"></i>Gestión de Alertas
                    </h1>
                    <div class="d-flex gap-2">
                        <a href="reportes.php?tabla=alertas" class="btn btn-outline-primary rounded-pill">
                            <i class="fas fa-chart-bar me-2"></i>Ver Reportes
                        </a>
                        <button type="button" class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#modalCrear">
                            <i class="fas fa-plus me-2"></i>Nueva Alerta
                        </button>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stats-card">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h3 class="mb-0"><?= $stats['total'] ?></h3>
                                    <p class="mb-0 opacity-75">Total Alertas</p>
                                </div>
                                <i class="fas fa-bell fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h3 class="mb-0"><?= $stats['activas'] ?></h3>
                                    <p class="mb-0 opacity-75">Activas</p>
                                </div>
                                <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h3 class="mb-0"><?= $stats['resueltas'] ?></h3>
                                    <p class="mb-0 opacity-75">Resueltas</p>
                                </div>
                                <i class="fas fa-check-circle fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #8e44ad 0%, #9b59b6 100%);">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h3 class="mb-0"><?= $stats['criticas'] ?></h3>
                                    <p class="mb-0 opacity-75">Críticas</p>
                                </div>
                                <i class="fas fa-fire fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel de filtros -->
                <div class="filter-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <div class="search-container">
                                <i class="fas fa-search"></i>
                                <input type="text" name="filtro_tipo" class="form-control" placeholder="Buscar por tipo..." value="<?= htmlspecialchars($filtro_tipo) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="filtro_nivel" class="form-select">
                                <option value="">Todos los niveles</option>
                                <option value="Crítico" <?= $filtro_nivel == 'Crítico' ? 'selected' : '' ?>>Crítico</option>
                                <option value="Alto" <?= $filtro_nivel == 'Alto' ? 'selected' : '' ?>>Alto</option>
                                <option value="Medio" <?= $filtro_nivel == 'Medio' ? 'selected' : '' ?>>Medio</option>
                                <option value="Bajo" <?= $filtro_nivel == 'Bajo' ? 'selected' : '' ?>>Bajo</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="filtro_estado" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="Activa" <?= $filtro_estado == 'Activa' ? 'selected' : '' ?>>Activa</option>
                                <option value="Resuelta" <?= $filtro_estado == 'Resuelta' ? 'selected' : '' ?>>Resuelta</option>
                                <option value="Pendiente" <?= $filtro_estado == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="filtro_producto" class="form-control" placeholder="Buscar producto..." value="<?= htmlspecialchars($filtro_producto) ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-gradient w-100">
                                <i class="fas fa-filter me-2"></i>Filtrar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Botón crear alerta -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Lista de Alertas</h5>
                    <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalCrearAlerta">
                        <i class="fas fa-plus me-2"></i>Nueva Alerta
                    </button>
                </div>

                <!-- Tabla de alertas -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="alertasTable">
                        <thead>
                            <tr>
                                <th><i class="fas fa-tag me-2"></i>Tipo</th>
                                <th><i class="fas fa-comment me-2"></i>Observación</th>
                                <th><i class="fas fa-level-up-alt me-2"></i>Nivel</th>
                                <th><i class="fas fa-calendar me-2"></i>Fecha</th>
                                <th><i class="fas fa-info-circle me-2"></i>Estado</th>
                                <th><i class="fas fa-box me-2"></i>Producto</th>
                                <th><i class="fas fa-layer-group me-2"></i>Categoría</th>
                                <th><i class="fas fa-cogs me-2"></i>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $nivel_class = strtolower($row['nivel_alerta']);
                                    $estado_class = strtolower(str_replace(' ', '', $row['estado']));
                                ?>
                                <tr class="alert-card alert-<?= $nivel_class ?>">
                                    <td>
                                        <i class="fas fa-bell me-2 text-primary"></i>
                                        <?= htmlspecialchars($row['tipo_alerta']) ?>
                                    </td>
                                    <td>
                                        <span class="text-muted" title="<?= htmlspecialchars($row['observacion']) ?>">
                                            <?= strlen($row['observacion']) > 50 ? htmlspecialchars(substr($row['observacion'], 0, 50)) . '...' : htmlspecialchars($row['observacion']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-nivel badge-<?= $nivel_class ?>">
                                            <?php 
                                            switch($row['nivel_alerta']) {
                                                case 'Crítico': echo '<i class="fas fa-fire me-1"></i>'; break;
                                                case 'Alto': echo '<i class="fas fa-exclamation-triangle me-1"></i>'; break;
                                                case 'Medio': echo '<i class="fas fa-exclamation me-1"></i>'; break;
                                                case 'Bajo': echo '<i class="fas fa-info me-1"></i>'; break;
                                            }
                                            ?>
                                            <?= htmlspecialchars($row['nivel_alerta']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar-alt me-2 text-muted"></i>
                                        <?= date('d/m/Y', strtotime($row['fecha_generacion'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-estado estado-<?= $estado_class ?>">
                                            <?= htmlspecialchars($row['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['producto_nombre']): ?>
                                            <div>
                                                <strong><?= htmlspecialchars($row['producto_nombre']) ?></strong>
                                                <?php if ($row['producto_stock'] !== null): ?>
                                                    <br><small class="text-muted">Stock: <?= $row['producto_stock'] ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Sin producto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['categoria_nombre']): ?>
                                            <div>
                                                <strong><?= htmlspecialchars($row['categoria_nombre']) ?></strong>
                                                <?php if ($row['subcategoria_nombre']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($row['subcategoria_nombre']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-outline-primary btn-sm" onclick="editarAlerta(<?= $row['id_alerta'] ?>, '<?= addslashes($row['tipo_alerta']) ?>', '<?= addslashes($row['observacion']) ?>', '<?= $row['nivel_alerta'] ?>', '<?= $row['estado'] ?>', <?= $row['id_prod'] ?? 'null' ?>)" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($row['estado'] == 'Activa'): ?>
                                            <button class="btn btn-outline-success btn-sm" onclick="cambiarEstado(<?= $row['id_alerta'] ?>, 'Resuelta')" title="Resolver">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-danger btn-sm" onclick="eliminarAlerta(<?= $row['id_alerta'] ?>)" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-bell fa-3x mb-3 opacity-50"></i>
                                            <h5>No hay alertas registradas</h5>
                                            <p>Crea una nueva alerta para comenzar</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Crear/Editar Alerta -->
<div class="modal fade" id="modalCrearAlerta" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">
                    <i class="fas fa-plus me-2"></i>Nueva Alerta
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAlerta">
                    <input type="hidden" id="alertaId" name="id_alerta">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-tag me-2"></i>Tipo de Alerta
                            </label>
                            <select name="tipo_alerta" id="tipoAlerta" class="form-select" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="Stock Bajo">Stock Bajo</option>
                                <option value="Vencimiento">Vencimiento</option>
                                <option value="Calidad">Problema de Calidad</option>
                                <option value="Proveedor">Problema con Proveedor</option>
                                <option value="Sistema">Alerta del Sistema</option>
                                <option value="Mantenimiento">Mantenimiento</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-level-up-alt me-2"></i>Nivel de Alerta
                            </label>
                            <select name="nivel_alerta" id="nivelAlerta" class="form-select" required>
                                <option value="">Seleccionar nivel...</option>
                                <option value="Bajo">Bajo</option>
                                <option value="Medio">Medio</option>
                                <option value="Alto">Alto</option>
                                <option value="Crítico">Crítico</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-info-circle me-2"></i>Estado
                            </label>
                            <select name="estado" id="estado" class="form-select" required>
                                <option value="Activa">Activa</option>
                                <option value="Pendiente">Pendiente</option>
                                <option value="Resuelta">Resuelta</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-box me-2"></i>Producto (Opcional)
                            </label>
                            <select name="id_prod" id="productoSelect" class="form-select">
                                <option value="">Sin producto asociado</option>
                                <?php 
                                $productos->data_seek(0);
                                while($prod = $productos->fetch_assoc()): 
                                ?>
                                <option value="<?= $prod['id_prod'] ?>"><?= htmlspecialchars($prod['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-comment me-2"></i>Observación
                            </label>
                            <textarea name="observacion" id="observacion" class="form-control" rows="4" placeholder="Describe la alerta en detalle..." required></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-gradient" onclick="guardarAlerta()">
                    <i class="fas fa-save me-2"></i>Guardar Alerta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmación -->
<div class="modal fade" id="modalConfirmacion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Acción
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-question-circle fa-4x text-warning mb-3"></i>
                <h5 id="mensajeConfirmacion">¿Está seguro que desea realizar esta acción?</h5>
                <p class="text-muted" id="detalleConfirmacion">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmar">
                    <i class="fas fa-check me-2"></i>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let accionConfirmada = null;

function mostrarLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function ocultarLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

function mostrarNotificacion(mensaje, tipo = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${tipo} border-0 position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${tipo === 'success' ? 'check' : 'exclamation-triangle'} me-2"></i>
                ${mensaje}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    document.body.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    setTimeout(() => toast.remove(), 5000);
}

function limpiarFormulario() {
    document.getElementById('formAlerta').reset();
    document.getElementById('alertaId').value = '';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Nueva Alerta';
}

function editarAlerta(id, tipo, observacion, nivel, estado, idProd) {
    document.getElementById('alertaId').value = id;
    document.getElementById('tipoAlerta').value = tipo;
    document.getElementById('observacion').value = observacion;
    document.getElementById('nivelAlerta').value = nivel;
    document.getElementById('estado').value = estado;
    document.getElementById('productoSelect').value = idProd || '';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Alerta';
    
    const modal = new bootstrap.Modal(document.getElementById('modalCrearAlerta'));
    modal.show();
}

function guardarAlerta() {
    const form = document.getElementById('formAlerta');
    const formData = new FormData(form);
    const id = document.getElementById('alertaId').value;
    
    formData.append('action', id ? 'editar' : 'crear');
    
    mostrarLoading();
    
    fetch('alertas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        ocultarLoading();
        if (data.success) {
            mostrarNotificacion(data.message);
            location.reload();
        } else {
            mostrarNotificacion(data.message, 'danger');
        }
    })
    .catch(error => {
        ocultarLoading();
        mostrarNotificacion('Error de conexión', 'danger');
    });
}

function eliminarAlerta(id) {
    document.getElementById('mensajeConfirmacion').textContent = '¿Está seguro que desea eliminar esta alerta?';
    document.getElementById('detalleConfirmacion').textContent = 'Esta acción no se puede deshacer.';
    
    accionConfirmada = () => {
        mostrarLoading();
        
        const formData = new FormData();
        formData.append('action', 'eliminar');
        formData.append('id_alerta', id);
        
        fetch('alertas.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            ocultarLoading();
            if (data.success) {
                mostrarNotificacion(data.message);
                location.reload();
            } else {
                mostrarNotificacion(data.message, 'danger');
            }
        })
        .catch(error => {
            ocultarLoading();
            mostrarNotificacion('Error de conexión', 'danger');
        });
    };
    
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
    modal.show();
}

function cambiarEstado(id, nuevoEstado) {
    document.getElementById('mensajeConfirmacion').textContent = `¿Desea marcar esta alerta como ${nuevoEstado.toLowerCase()}?`;
    document.getElementById('detalleConfirmacion').textContent = 'El estado de la alerta será actualizado.';
    
    accionConfirmada = () => {
        mostrarLoading();
        
        const formData = new FormData();
        formData.append('action', 'cambiar_estado');
        formData.append('id_alerta', id);
        formData.append('nuevo_estado', nuevoEstado);
        
        fetch('alertas.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            ocultarLoading();
            if (data.success) {
                mostrarNotificacion(data.message);
                location.reload();
            } else {
                mostrarNotificacion(data.message, 'danger');
            }
        })
        .catch(error => {
            ocultarLoading();
            mostrarNotificacion('Error de conexión', 'danger');
        });
    };
    
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
    modal.show();
}

document.getElementById('btnConfirmar').addEventListener('click', function() {
    if (accionConfirmada) {
        accionConfirmada();
        accionConfirmada = null;
    }
    bootstrap.Modal.getInstance(document.getElementById('modalConfirmacion')).hide();
});

document.getElementById('modalCrearAlerta').addEventListener('hidden.bs.modal', function() {
    limpiarFormulario();
});

// Actualización automática cada 30 segundos para alertas críticas
setInterval(() => {
    const criticasCount = document.querySelectorAll('.badge-critico').length;
    if (criticasCount > 0) {
        document.title = `(${criticasCount}) Alertas Críticas - Inventixor`;
    }
}, 30000);
</script>
</body>
</html>
