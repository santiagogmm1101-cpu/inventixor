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

// Verificar permisos de administración
$es_admin = $_SESSION['rol'] === 'admin';
$es_coordinador = $_SESSION['rol'] === 'coordinador' || $_SESSION['rol'] === 'admin';

// Manejar acciones AJAX
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'crear':
            if (!$es_coordinador) {
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para crear usuarios']);
                exit;
            }
            
            $num_doc = intval($_POST['num_doc']);
            $tipo_documento = intval($_POST['tipo_documento']);
            $apellidos = trim($_POST['apellidos']);
            $nombres = trim($_POST['nombres']);
            $telefono = $_POST['telefono'];
            $correo = trim($_POST['correo']);
            $cargo = trim($_POST['cargo']);
            $rol = $_POST['rol'];
            $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
            
            // Validar que solo admin puede crear otros admins
            if ($rol === 'admin' && !$es_admin) {
                echo json_encode(['success' => false, 'message' => 'Solo un administrador puede crear otros administradores']);
                exit;
            }
            
            // Verificar si el usuario ya existe
            $check_stmt = $db->conn->prepare("SELECT COUNT(*) FROM Users WHERE num_doc = ? OR correo = ?");
            $check_stmt->bind_param('is', $num_doc, $correo);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->fetch_row()[0];
            $check_stmt->close();
            
            if ($exists > 0) {
                echo json_encode(['success' => false, 'message' => 'Ya existe un usuario con este documento o correo']);
                exit;
            }
            
            $stmt = $db->conn->prepare("INSERT INTO Users (num_doc, tipo_documento, apellidos, nombres, telefono, correo, cargo, rol, contrasena) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iisssssss', $num_doc, $tipo_documento, $apellidos, $nombres, $telefono, $correo, $cargo, $rol, $contrasena);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear el usuario']);
            }
            $stmt->close();
            exit;
            
        case 'editar':
            if (!$es_coordinador) {
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar usuarios']);
                exit;
            }
            
            $num_doc = intval($_POST['num_doc']);
            $apellidos = trim($_POST['apellidos']);
            $nombres = trim($_POST['nombres']);
            $telefono = $_POST['telefono'];
            $correo = trim($_POST['correo']);
            $cargo = trim($_POST['cargo']);
            $rol = $_POST['rol'];
            
            // Validar que solo admin puede cambiar roles a admin
            if ($rol === 'admin' && !$es_admin) {
                echo json_encode(['success' => false, 'message' => 'Solo un administrador puede asignar el rol de administrador']);
                exit;
            }
            
            // No permitir que un usuario se quite a sí mismo los permisos de admin
            if ($_SESSION['user']['num_doc'] == $num_doc && $_SESSION['rol'] === 'admin' && $rol !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'No puedes quitarte los permisos de administrador']);
                exit;
            }
            
            if (!empty($_POST['nueva_contrasena'])) {
                $nueva_contrasena = password_hash($_POST['nueva_contrasena'], PASSWORD_DEFAULT);
                $stmt = $db->conn->prepare("UPDATE Users SET apellidos=?, nombres=?, telefono=?, correo=?, cargo=?, rol=?, contrasena=? WHERE num_doc=?");
                $stmt->bind_param('sssssssi', $apellidos, $nombres, $telefono, $correo, $cargo, $rol, $nueva_contrasena, $num_doc);
            } else {
                $stmt = $db->conn->prepare("UPDATE Users SET apellidos=?, nombres=?, telefono=?, correo=?, cargo=?, rol=? WHERE num_doc=?");
                $stmt->bind_param('ssssssi', $apellidos, $nombres, $telefono, $correo, $cargo, $rol, $num_doc);
            }
            
            if ($stmt->execute()) {
                // Actualizar sesión si el usuario editó su propio perfil
                if ($_SESSION['user']['num_doc'] == $num_doc) {
                    $_SESSION['user']['nombres'] = $nombres;
                    $_SESSION['user']['apellidos'] = $apellidos;
                    $_SESSION['rol'] = $rol;
                }
                echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el usuario']);
            }
            $stmt->close();
            exit;
            
        case 'eliminar':
            if (!$es_admin) {
                echo json_encode(['success' => false, 'message' => 'Solo los administradores pueden eliminar usuarios']);
                exit;
            }
            
            $num_doc = intval($_POST['num_doc']);
            
            // No permitir eliminar el propio usuario
            if ($_SESSION['user']['num_doc'] == $num_doc) {
                echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propio usuario']);
                exit;
            }
            
            $stmt = $db->conn->prepare("DELETE FROM Users WHERE num_doc=?");
            $stmt->bind_param('i', $num_doc);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar el usuario']);
            }
            $stmt->close();
            exit;
    }
}

// Filtros
$filtro_rol = isset($_GET['filtro_rol']) ? $_GET['filtro_rol'] : '';
$filtro_cargo = isset($_GET['filtro_cargo']) ? $_GET['filtro_cargo'] : '';
$filtro_nombre = isset($_GET['filtro_nombre']) ? $_GET['filtro_nombre'] : '';

// Construir consulta con filtros y JOIN con productos para estadísticas
$sql = "SELECT 
            u.num_doc, 
            u.tipo_documento, 
            u.apellidos, 
            u.nombres, 
            u.telefono, 
            u.correo, 
            u.cargo, 
            u.rol,
            COUNT(DISTINCT p.id_prod) as productos_asignados,
            COUNT(DISTINCT r.id_repor) as reportes_creados
        FROM Users u
        LEFT JOIN Productos p ON u.num_doc = p.num_doc
        LEFT JOIN Reportes r ON u.num_doc = r.num_doc";

$where_conditions = [];
$params = [];
$types = '';

if (!empty($filtro_rol)) {
    $where_conditions[] = "u.rol = ?";
    $params[] = $filtro_rol;
    $types .= 's';
}

if (!empty($filtro_cargo)) {
    $where_conditions[] = "u.cargo LIKE ?";
    $params[] = "%$filtro_cargo%";
    $types .= 's';
}

if (!empty($filtro_nombre)) {
    $where_conditions[] = "(u.nombres LIKE ? OR u.apellidos LIKE ?)";
    $params[] = "%$filtro_nombre%";
    $params[] = "%$filtro_nombre%";
    $types .= 'ss';
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " GROUP BY u.num_doc ORDER BY u.rol DESC, u.nombres ASC";

if (!empty($params)) {
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $db->conn->query($sql);
}

// Obtener estadísticas
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) as administradores,
                SUM(CASE WHEN rol = 'coordinador' THEN 1 ELSE 0 END) as coordinadores,
                SUM(CASE WHEN rol = 'auxiliar' THEN 1 ELSE 0 END) as auxiliares
              FROM Users";
$stats_result = $db->conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Insertar usuario admin de emergencia si no existe
$adminCheck = $db->conn->query("SELECT COUNT(*) FROM Users WHERE num_doc=1001")->fetch_row()[0];
if ($adminCheck == 0) {
    $contrasena_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $db->conn->query("INSERT INTO Users (num_doc, tipo_documento, apellidos, nombres, telefono, correo, cargo, rol, contrasena) VALUES (1001, 1, 'Administrador', 'Principal', 3000000000, 'admin@inventixor.com', 'Administrador del Sistema', 'admin', '$contrasena_hash')");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Inventixor</title>
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
        
        .stats-card.admin {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        
        .stats-card.coordinador {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        
        .stats-card.auxiliar {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
        }
        
        .user-card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 5px solid;
            margin-bottom: 15px;
        }
        
        .user-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .user-admin { border-left-color: #e74c3c; }
        .user-coordinador { border-left-color: #f39c12; }
        .user-auxiliar { border-left-color: #27ae60; }
        
        .badge-rol {
            font-size: 0.85em;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }
        
        .badge-coordinador {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }
        
        .badge-auxiliar {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        
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
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        .avatar-container {
            position: relative;
            display: inline-block;
        }
        
        .avatar-status {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 16px;
            height: 16px;
            border: 2px solid white;
            border-radius: 50%;
        }
        
        .status-online { background-color: #27ae60; }
        .status-offline { background-color: #95a5a6; }
        
        .permission-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .can-create { background-color: #27ae60; }
        .can-edit { background-color: #f39c12; }
        .can-delete { background-color: #e74c3c; }
        .no-permission { background-color: #95a5a6; }
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
                        <a class="nav-link d-flex align-items-center py-2 px-3" href="alertas.php">
                            <i class="fas fa-bell me-3"></i>
                            <span>Alertas</span>
                        </a>
                    </li>
                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center py-2 px-3 active" href="usuarios.php">
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

        <!-- Main Content -->
        <main class="col-md-9 col-lg-10 ms-md-auto">
            <div class="main-content fade-in">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="page-title">
                        <i class="fas fa-users me-3"></i>Gestión de Usuarios
                    </h1>
                    <div class="d-flex gap-2">
                        <div class="d-flex align-items-center me-3">
                            <span class="permission-indicator <?= $es_admin ? 'can-delete' : ($es_coordinador ? 'can-edit' : 'no-permission') ?>"></span>
                            <small class="text-muted">
                                <?= $es_admin ? 'Administrador Total' : ($es_coordinador ? 'Coordinador' : 'Solo Lectura') ?>
                            </small>
                        </div>
                        <a href="reportes.php?tabla=usuarios" class="btn btn-outline-primary rounded-pill">
                            <i class="fas fa-chart-bar me-2"></i>Ver Reportes
                        </a>
                        <?php if ($es_coordinador): ?>
                        <button type="button" class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#modalCrear">
                            <i class="fas fa-plus me-2"></i>Nuevo Usuario
                        </button>
                        <?php endif; ?>
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                                <div class="avatar-container">
                                    <img src="https://ui-avatars.com/api/?name=<?=urlencode($_SESSION['user']['nombres']??'U')?>&background=667eea&color=fff" alt="Avatar" width="35" height="35" class="rounded-circle">
                                    <div class="avatar-status status-online"></div>
                                </div>
                                <span class="fw-semibold ms-2"><?= htmlspecialchars($_SESSION['user']['nombres']??'Usuario') ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><span class="dropdown-item-text"><strong>Rol:</strong> <?= htmlspecialchars($_SESSION['rol']??'') ?></span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stats-card">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h3 class="mb-0"><?= $stats['total'] ?></h3>
                                    <p class="mb-0 opacity-75">Total Usuarios</p>
                                </div>
                                <i class="fas fa-users fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stats-card admin">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h3 class="mb-0"><?= $stats['administradores'] ?></h3>
                                    <p class="mb-0 opacity-75">Administradores</p>
                                </div>
                                <i class="fas fa-crown fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stats-card coordinador">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h3 class="mb-0"><?= $stats['coordinadores'] ?></h3>
                                    <p class="mb-0 opacity-75">Coordinadores</p>
                                </div>
                                <i class="fas fa-user-tie fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="stats-card auxiliar">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h3 class="mb-0"><?= $stats['auxiliares'] ?></h3>
                                    <p class="mb-0 opacity-75">Auxiliares</p>
                                </div>
                                <i class="fas fa-user fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel de filtros -->
                <div class="filter-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="search-container">
                                <i class="fas fa-search"></i>
                                <input type="text" name="filtro_nombre" class="form-control" placeholder="Buscar por nombre..." value="<?= htmlspecialchars($filtro_nombre) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="filtro_rol" class="form-select">
                                <option value="">Todos los roles</option>
                                <option value="admin" <?= $filtro_rol == 'admin' ? 'selected' : '' ?>>Administrador</option>
                                <option value="coordinador" <?= $filtro_rol == 'coordinador' ? 'selected' : '' ?>>Coordinador</option>
                                <option value="auxiliar" <?= $filtro_rol == 'auxiliar' ? 'selected' : '' ?>>Auxiliar</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="filtro_cargo" class="form-control" placeholder="Buscar cargo..." value="<?= htmlspecialchars($filtro_cargo) ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-gradient w-100">
                                <i class="fas fa-filter me-2"></i>Filtrar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Botón crear usuario -->
                <?php if ($es_coordinador): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Lista de Usuarios</h5>
                    <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">
                        <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
                    </button>
                </div>
                <?php else: ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Lista de Usuarios</h5>
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-2"></i>Solo tienes permisos de lectura
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tabla de usuarios -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="usuariosTable">
                        <thead>
                            <tr>
                                <th><i class="fas fa-id-card me-2"></i>Usuario</th>
                                <th><i class="fas fa-envelope me-2"></i>Contacto</th>
                                <th><i class="fas fa-briefcase me-2"></i>Cargo</th>
                                <th><i class="fas fa-shield-alt me-2"></i>Rol</th>
                                <th><i class="fas fa-chart-bar me-2"></i>Estadísticas</th>
                                <?php if ($es_coordinador): ?>
                                <th><i class="fas fa-cogs me-2"></i>Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $rol_class = strtolower($row['rol']);
                                    $es_usuario_actual = $_SESSION['user']['num_doc'] == $row['num_doc'];
                                ?>
                                <tr class="user-card user-<?= $rol_class ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-container me-3">
                                                <img src="https://ui-avatars.com/api/?name=<?=urlencode($row['nombres'].' '.$row['apellidos'])?>&background=<?= $row['rol'] == 'admin' ? 'e74c3c' : ($row['rol'] == 'coordinador' ? 'f39c12' : '27ae60') ?>&color=fff" alt="Avatar" width="45" height="45" class="rounded-circle">
                                                <div class="avatar-status status-online"></div>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($row['nombres'].' '.$row['apellidos']) ?></strong>
                                                <?php if ($es_usuario_actual): ?>
                                                    <span class="badge bg-info ms-2">Tú</span>
                                                <?php endif; ?>
                                                <br><small class="text-muted">Doc: <?= htmlspecialchars($row['num_doc']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="fas fa-envelope text-primary me-2"></i><?= htmlspecialchars($row['correo']) ?>
                                            <br><i class="fas fa-phone text-success me-2"></i><?= htmlspecialchars($row['telefono']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold"><?= htmlspecialchars($row['cargo']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-rol badge-<?= $rol_class ?>">
                                            <?php 
                                            switch($row['rol']) {
                                                case 'admin': echo '<i class="fas fa-crown me-1"></i>Administrador'; break;
                                                case 'coordinador': echo '<i class="fas fa-user-tie me-1"></i>Coordinador'; break;
                                                case 'auxiliar': echo '<i class="fas fa-user me-1"></i>Auxiliar'; break;
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <span class="badge bg-primary" title="Productos asignados">
                                                <i class="fas fa-box me-1"></i><?= $row['productos_asignados'] ?>
                                            </span>
                                            <span class="badge bg-success" title="Reportes creados">
                                                <i class="fas fa-chart-line me-1"></i><?= $row['reportes_creados'] ?>
                                            </span>
                                        </div>
                                    </td>
                                    <?php if ($es_coordinador): ?>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-outline-primary btn-sm" onclick="editarUsuario(<?= $row['num_doc'] ?>, '<?= addslashes($row['nombres']) ?>', '<?= addslashes($row['apellidos']) ?>', '<?= $row['telefono'] ?>', '<?= addslashes($row['correo']) ?>', '<?= addslashes($row['cargo']) ?>', '<?= $row['rol'] ?>', <?= $row['tipo_documento'] ?>)" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($es_admin && !$es_usuario_actual): ?>
                                            <button class="btn btn-outline-danger btn-sm" onclick="eliminarUsuario(<?= $row['num_doc'] ?>, '<?= addslashes($row['nombres'].' '.$row['apellidos']) ?>')" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $es_coordinador ? '6' : '5' ?>" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-users fa-3x mb-3 opacity-50"></i>
                                            <h5>No hay usuarios registrados</h5>
                                            <p>No se encontraron usuarios con los filtros aplicados</p>
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

<?php if ($es_coordinador): ?>
<!-- Modal Crear/Editar Usuario -->
<div class="modal fade" id="modalCrearUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">
                    <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formUsuario">
                    <input type="hidden" id="usuarioId" name="num_doc_original">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-id-card me-2"></i>Número de Documento
                            </label>
                            <input type="number" name="num_doc" id="numDoc" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-file-alt me-2"></i>Tipo de Documento
                            </label>
                            <select name="tipo_documento" id="tipoDocumento" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                <option value="1">Cédula de Ciudadanía</option>
                                <option value="2">Tarjeta de Identidad</option>
                                <option value="3">Cédula de Extranjería</option>
                                <option value="4">Pasaporte</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user me-2"></i>Nombres
                            </label>
                            <input type="text" name="nombres" id="nombres" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user me-2"></i>Apellidos
                            </label>
                            <input type="text" name="apellidos" id="apellidos" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-phone me-2"></i>Teléfono
                            </label>
                            <input type="tel" name="telefono" id="telefono" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-envelope me-2"></i>Correo Electrónico
                            </label>
                            <input type="email" name="correo" id="correo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-briefcase me-2"></i>Cargo
                            </label>
                            <input type="text" name="cargo" id="cargo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-shield-alt me-2"></i>Rol
                            </label>
                            <select name="rol" id="rol" class="form-select" required>
                                <option value="">Seleccionar rol...</option>
                                <option value="auxiliar">Auxiliar</option>
                                <option value="coordinador">Coordinador</option>
                                <?php if ($es_admin): ?>
                                <option value="admin">Administrador</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="contrasenaContainer">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-lock me-2"></i>Contraseña
                            </label>
                            <input type="password" name="contrasena" id="contrasena" class="form-control" required>
                        </div>
                        <div class="col-md-6" id="nuevaContrasenaContainer" style="display: none;">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-key me-2"></i>Nueva Contraseña (Opcional)
                            </label>
                            <input type="password" name="nueva_contrasena" id="nuevaContrasena" class="form-control">
                            <small class="text-muted">Dejar vacío para mantener la actual</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-gradient" onclick="guardarUsuario()">
                    <i class="fas fa-save me-2"></i>Guardar Usuario
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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
const esCoordinador = <?= json_encode($es_coordinador) ?>;
const esAdmin = <?= json_encode($es_admin) ?>;

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
    document.getElementById('formUsuario').reset();
    document.getElementById('usuarioId').value = '';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>Nuevo Usuario';
    document.getElementById('contrasenaContainer').style.display = 'block';
    document.getElementById('nuevaContrasenaContainer').style.display = 'none';
    document.getElementById('contrasena').required = true;
}

function editarUsuario(numDoc, nombres, apellidos, telefono, correo, cargo, rol, tipoDocumento) {
    if (!esCoordinador) {
        mostrarNotificacion('No tienes permisos para editar usuarios', 'danger');
        return;
    }
    
    document.getElementById('usuarioId').value = numDoc;
    document.getElementById('numDoc').value = numDoc;
    document.getElementById('numDoc').readOnly = true;
    document.getElementById('tipoDocumento').value = tipoDocumento;
    document.getElementById('nombres').value = nombres;
    document.getElementById('apellidos').value = apellidos;
    document.getElementById('telefono').value = telefono;
    document.getElementById('correo').value = correo;
    document.getElementById('cargo').value = cargo;
    document.getElementById('rol').value = rol;
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i>Editar Usuario';
    document.getElementById('contrasenaContainer').style.display = 'none';
    document.getElementById('nuevaContrasenaContainer').style.display = 'block';
    document.getElementById('contrasena').required = false;
    
    const modal = new bootstrap.Modal(document.getElementById('modalCrearUsuario'));
    modal.show();
}

function guardarUsuario() {
    if (!esCoordinador) {
        mostrarNotificacion('No tienes permisos para esta acción', 'danger');
        return;
    }
    
    const form = document.getElementById('formUsuario');
    const formData = new FormData(form);
    const id = document.getElementById('usuarioId').value;
    
    formData.append('action', id ? 'editar' : 'crear');
    
    mostrarLoading();
    
    fetch('usuarios.php', {
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

function eliminarUsuario(numDoc, nombreCompleto) {
    if (!esAdmin) {
        mostrarNotificacion('Solo los administradores pueden eliminar usuarios', 'danger');
        return;
    }
    
    document.getElementById('mensajeConfirmacion').textContent = `¿Está seguro que desea eliminar al usuario "${nombreCompleto}"?`;
    document.getElementById('detalleConfirmacion').textContent = 'Esta acción eliminará permanentemente el usuario y no se puede deshacer.';
    
    accionConfirmada = () => {
        mostrarLoading();
        
        const formData = new FormData();
        formData.append('action', 'eliminar');
        formData.append('num_doc', numDoc);
        
        fetch('usuarios.php', {
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

<?php if ($es_coordinador): ?>
document.getElementById('modalCrearUsuario').addEventListener('hidden.bs.modal', function() {
    limpiarFormulario();
    document.getElementById('numDoc').readOnly = false;
});
<?php endif; ?>

// Validación en tiempo real del número de documento
document.getElementById('numDoc')?.addEventListener('input', function() {
    const numDoc = this.value;
    if (numDoc.length >= 6) {
        // Verificar si el documento ya existe (opcional)
        // Implementar verificación AJAX aquí si es necesario
    }
});
</script>
</body>
</html>