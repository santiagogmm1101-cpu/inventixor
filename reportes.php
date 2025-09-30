<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
require_once 'app/helpers/Database.php';

$db = new Database();

// Asegurar que $_SESSION['rol'] esté definido
if (!isset($_SESSION['rol'])) {
    if (isset($_SESSION['user']['num_doc'])) {
        $num_doc = $_SESSION['user']['num_doc'];
        $rolRes = $db->conn->query("SELECT rol FROM Users WHERE num_doc = '$num_doc'");
        if ($rolRes && $rolRow = $rolRes->fetch_assoc()) {
            $_SESSION['rol'] = $rolRow['rol'];
        } else {
            $_SESSION['rol'] = '';
        }
    } else {
        $_SESSION['rol'] = '';
    }
}

$message = '';
$error = '';

// Eliminar reporte
if (isset($_GET['eliminar']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'coordinador')) {
    $id_repor = intval($_GET['eliminar']);
    
    $stmt = $db->conn->prepare("DELETE FROM Reportes WHERE id_repor = ?");
    $stmt->bind_param('i', $id_repor);
    $stmt->execute();
    $stmt->close();
    header('Location: reportes.php?msg=eliminado');
    exit;
}

// Modificar reporte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modificar_reporte'])) {
    if (in_array($_SESSION['rol'], ['admin', 'coordinador'])) {
        $id_repor = intval($_POST['id_repor']);
        $nombre_reporte = $_POST['nombre_reporte'];
        $descripcion = $_POST['descripcion'];
        $num_doc = $_POST['num_doc'];
        $id_nit = $_POST['id_nit'];
        $id_prod = $_POST['id_prod'];
        
        $stmt = $db->conn->prepare("UPDATE Reportes SET nombre=?, descripcion=?, num_doc=?, id_nit=?, id_prod=? WHERE id_repor=?");
        $stmt->bind_param('sssiiii', $nombre_reporte, $descripcion, $num_doc, $id_nit, $id_prod, $id_repor);
        $stmt->execute();
        $stmt->close();
        header('Location: reportes.php?msg=modificado');
        exit;
    } else {
        $error = 'No tienes permisos para modificar reportes.';
    }
}

// Crear reporte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_reporte'])) {
    if (in_array($_SESSION['rol'], ['admin', 'coordinador', 'auxiliar'])) {
        $nombre_reporte = $_POST['nombre_reporte'];
        $descripcion = $_POST['descripcion'];
        $num_doc = $_POST['num_doc'];
        $id_nit = $_POST['id_nit'] ?: null;
        $id_prod = $_POST['id_prod'] ?: null;
        
        $stmt = $db->conn->prepare("INSERT INTO Reportes (nombre, descripcion, num_doc, id_nit, id_prod, fecha_hora) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('sssii', $nombre_reporte, $descripcion, $num_doc, $id_nit, $id_prod);
        $stmt->execute();
        $stmt->close();
        header('Location: reportes.php?msg=creado');
        exit;
    } else {
        $error = 'No tienes permisos para crear reportes.';
    }
}

// Exportar datos
if (isset($_GET['exportar']) && isset($_GET['formato'])) {
    $formato = $_GET['formato'];
    $tipo = $_GET['exportar'];
    
    // Consulta base para exportación
    $sql = "SELECT r.id_repor, r.nombre, r.descripcion, r.fecha_hora,
                   u.nombres as usuario_nombre,
                   pr.razon_social as proveedor_nombre,
                   p.nombre as producto_nombre,
                   COUNT(DISTINCT a.id_alerta) as total_alertas
            FROM Reportes r
            LEFT JOIN Users u ON r.num_doc = u.num_doc
            LEFT JOIN Proveedores pr ON r.id_nit = pr.id_nit
            LEFT JOIN Productos p ON r.id_prod = p.id_prod
            LEFT JOIN Alertas a ON p.id_prod = a.id_prod
            GROUP BY r.id_repor
            ORDER BY r.fecha_hora DESC";
    
    $result = $db->conn->query($sql);
    
    if ($formato === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="reportes_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Nombre', 'Descripción', 'Fecha', 'Usuario', 'Proveedor', 'Producto', 'Alertas']);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['id_repor'],
                $row['nombre'],
                $row['descripcion'],
                $row['fecha_hora'],
                $row['usuario_nombre'],
                $row['proveedor_nombre'],
                $row['producto_nombre'],
                $row['total_alertas']
            ]);
        }
        fclose($output);
        exit;
    }
}

// Mensajes
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'creado':
            $message = 'Reporte creado correctamente.';
            break;
        case 'modificado':
            $message = 'Reporte modificado correctamente.';
            break;
        case 'eliminado':
            $message = 'Reporte eliminado correctamente.';
            break;
    }
}

// Filtros
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_proveedor = $_GET['proveedor'] ?? '';
$filtro_producto = $_GET['producto'] ?? '';
$filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Consulta principal con JOIN completo
$sql = "SELECT r.id_repor, r.nombre, r.descripcion, r.fecha_hora,
               u.num_doc, u.nombres as usuario_nombre, u.rol as usuario_rol,
               pr.id_nit, pr.razon_social as proveedor_nombre,
               p.id_prod, p.nombre as producto_nombre, p.stock as producto_stock,
               c.nombre as categoria_nombre,
               sc.nombre as subcategoria_nombre,
               COUNT(DISTINCT a.id_alerta) as total_alertas,
               COUNT(DISTINCT s.id_salida) as total_salidas
        FROM Reportes r
        LEFT JOIN Users u ON r.num_doc = u.num_doc
        LEFT JOIN Proveedores pr ON r.id_nit = pr.id_nit
        LEFT JOIN Productos p ON r.id_prod = p.id_prod
        LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
        LEFT JOIN Categoria c ON sc.id_categ = c.id_categ
        LEFT JOIN Alertas a ON p.id_prod = a.id_prod
        LEFT JOIN Salidas s ON p.id_prod = s.id_prod";

$where_conditions = [];
$params = [];
$types = '';

if ($filtro_usuario) {
    $where_conditions[] = "u.nombres LIKE ?";
    $params[] = "%$filtro_usuario%";
    $types .= 's';
}

if ($filtro_proveedor) {
    $where_conditions[] = "pr.id_nit = ?";
    $params[] = $filtro_proveedor;
    $types .= 'i';
}

if ($filtro_producto) {
    $where_conditions[] = "p.id_prod = ?";
    $params[] = $filtro_producto;
    $types .= 'i';
}

if ($filtro_fecha_desde) {
    $where_conditions[] = "DATE(r.fecha_hora) >= ?";
    $params[] = $filtro_fecha_desde;
    $types .= 's';
}

if ($filtro_fecha_hasta) {
    $where_conditions[] = "DATE(r.fecha_hora) <= ?";
    $params[] = $filtro_fecha_hasta;
    $types .= 's';
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " GROUP BY r.id_repor ORDER BY r.fecha_hora DESC";

if (!empty($params)) {
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $db->conn->query($sql);
}

// Obtener datos para selects
$usuarios = $db->conn->query("SELECT num_doc, nombres FROM Users ORDER BY nombres");
$proveedores = $db->conn->query("SELECT id_nit, razon_social FROM Proveedores ORDER BY razon_social");
$productos = $db->conn->query("SELECT id_prod, nombre FROM Productos ORDER BY nombre");

// Estadísticas y gráficos
$stats = $db->conn->query("SELECT 
    COUNT(*) as total_reportes,
    COUNT(CASE WHEN DATE(fecha_hora) = CURDATE() THEN 1 END) as reportes_hoy,
    COUNT(CASE WHEN DATE(fecha_hora) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as reportes_semana,
    COUNT(CASE WHEN DATE(fecha_hora) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as reportes_mes
    FROM Reportes")->fetch_assoc();

// Datos para gráficos
$reportes_por_mes = $db->conn->query("SELECT 
    DATE_FORMAT(fecha_hora, '%Y-%m') as mes,
    COUNT(*) as cantidad
    FROM Reportes 
    WHERE fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha_hora, '%Y-%m')
    ORDER BY mes");

$reportes_por_usuario = $db->conn->query("SELECT 
    u.nombres,
    COUNT(r.id_repor) as cantidad
    FROM Users u
    LEFT JOIN Reportes r ON u.num_doc = r.num_doc
    GROUP BY u.num_doc
    ORDER BY cantidad DESC
    LIMIT 5");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reportes - Inventixor</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="public/css/style.css">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --sidebar-width: 280px;
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
        
        .main-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stats-card .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .stats-card .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .filter-card, .form-card, .chart-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .table-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .btn-action {
            margin: 0 2px;
            padding: 0.25rem 0.5rem;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
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
                <a href="dashboard.php" class="menu-link">
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
                <a href="reportes.php" class="menu-link active">
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
        <!-- Header -->
        <div class="main-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-chart-bar me-2"></i>Gestión de Reportes</h2>
                        <p class="mb-0">Análisis completo del sistema con gráficos y exportación</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-light text-dark">
                            Rol: <?= htmlspecialchars($_SESSION['rol']??'') ?>
                        </span>
                        <?php if (in_array($_SESSION['rol'], ['admin', 'coordinador', 'auxiliar'])): ?>
                        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#createModal">
                            <i class="fas fa-plus me-2"></i>Nuevo Reporte
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4 animate-fade-in">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon text-primary">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stats-number text-primary"><?= $stats['total_reportes'] ?></div>
                    <div class="text-muted">Total Reportes</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon text-success">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stats-number text-success"><?= $stats['reportes_hoy'] ?></div>
                    <div class="text-muted">Reportes Hoy</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon text-info">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stats-number text-info"><?= $stats['reportes_semana'] ?></div>
                    <div class="text-muted">Esta Semana</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon text-warning">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stats-number text-warning"><?= $stats['reportes_mes'] ?></div>
                    <div class="text-muted">Este Mes</div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="chart-card animate-fade-in">
                    <h5><i class="fas fa-chart-line me-2"></i>Reportes por Mes</h5>
                    <div class="chart-container">
                        <canvas id="reportesPorMesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-card animate-fade-in">
                    <h5><i class="fas fa-chart-pie me-2"></i>Reportes por Usuario</h5>
                    <div class="chart-container">
                        <canvas id="reportesPorUsuarioChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show animate-fade-in" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show animate-fade-in" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Filtros y Exportación -->
        <div class="filter-card animate-fade-in">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-filter me-2"></i>Filtros y Exportación</h5>
                <div class="dropdown">
                    <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-download me-2"></i>Exportar
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="reportes.php?exportar=todos&formato=csv">
                            <i class="fas fa-file-csv me-2"></i>CSV
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Imprimir
                        </a></li>
                    </ul>
                </div>
            </div>
            <div class="row align-items-end">
                <div class="col-md-2">
                    <label for="filtroUsuario" class="form-label">Usuario:</label>
                    <input type="text" id="filtroUsuario" class="form-control" 
                           placeholder="Buscar..." value="<?= htmlspecialchars($filtro_usuario) ?>">
                </div>
                <div class="col-md-2">
                    <label for="filtroProveedor" class="form-label">Proveedor:</label>
                    <select id="filtroProveedor" class="form-select">
                        <option value="">Todos</option>
                        <?php while($prov = $proveedores->fetch_assoc()): ?>
                        <option value="<?= $prov['id_nit'] ?>" <?= $filtro_proveedor == $prov['id_nit'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prov['razon_social']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filtroProducto" class="form-label">Producto:</label>
                    <select id="filtroProducto" class="form-select">
                        <option value="">Todos</option>
                        <?php while($prod = $productos->fetch_assoc()): ?>
                        <option value="<?= $prod['id_prod'] ?>" <?= $filtro_producto == $prod['id_prod'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prod['nombre']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fechaDesde" class="form-label">Desde:</label>
                    <input type="date" id="fechaDesde" class="form-control" value="<?= htmlspecialchars($filtro_fecha_desde) ?>">
                </div>
                <div class="col-md-2">
                    <label for="fechaHasta" class="form-label">Hasta:</label>
                    <input type="date" id="fechaHasta" class="form-control" value="<?= htmlspecialchars($filtro_fecha_hasta) ?>">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100 mb-2" onclick="aplicarFiltros()">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                    <button class="btn btn-outline-secondary w-100" onclick="limpiarFiltros()">
                        <i class="fas fa-times me-1"></i>Limpiar
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla de Reportes -->
        <div class="table-card animate-fade-in">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="gradient-bg">
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                            <th><i class="fas fa-file-alt me-1"></i>Nombre</th>
                            <th><i class="fas fa-align-left me-1"></i>Descripción</th>
                            <th><i class="fas fa-user me-1"></i>Usuario</th>
                            <th><i class="fas fa-truck me-1"></i>Proveedor</th>
                            <th><i class="fas fa-box me-1"></i>Producto</th>
                            <th><i class="fas fa-calendar me-1"></i>Fecha</th>
                            <th><i class="fas fa-chart-line me-1"></i>Actividad</th>
                            <th><i class="fas fa-cogs me-1"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge bg-primary"><?= $row['id_repor'] ?></span></td>
                            <td><strong><?= htmlspecialchars($row['nombre']) ?></strong></td>
                            <td>
                                <small><?= htmlspecialchars(substr($row['descripcion'], 0, 100)) ?>
                                <?= strlen($row['descripcion']) > 100 ? '...' : '' ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['usuario_nombre'] ?? 'N/A') ?>
                                <?php if ($row['usuario_rol']): ?>
                                <small class="d-block">
                                    <span class="badge bg-info"><?= htmlspecialchars($row['usuario_rol']) ?></span>
                                </small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['proveedor_nombre'] ?? 'N/A') ?></td>
                            <td>
                                <?= htmlspecialchars($row['producto_nombre'] ?? 'N/A') ?>
                                <?php if ($row['producto_stock'] !== null): ?>
                                <small class="d-block text-muted">Stock: <?= $row['producto_stock'] ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small>
                                    <?= date('d/m/Y', strtotime($row['fecha_hora'])) ?><br>
                                    <?= date('H:i', strtotime($row['fecha_hora'])) ?>
                                </small>
                            </td>
                            <td>
                                <small>
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <span class="badge bg-warning"><?= $row['total_alertas'] ?></span> alertas<br>
                                    <i class="fas fa-sign-out-alt me-1"></i>
                                    <span class="badge bg-secondary"><?= $row['total_salidas'] ?></span> salidas
                                </small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-info btn-action" 
                                            onclick="verDetalles(<?= $row['id_repor'] ?>, '<?= addslashes($row['nombre']) ?>', '<?= addslashes($row['descripcion']) ?>', '<?= $row['fecha_hora'] ?>', '<?= addslashes($row['usuario_nombre'] ?? '') ?>', '<?= addslashes($row['proveedor_nombre'] ?? '') ?>', '<?= addslashes($row['producto_nombre'] ?? '') ?>')"
                                            title="Ver Detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if (in_array($_SESSION['rol'], ['admin', 'coordinador'])): ?>
                                    <button type="button" class="btn btn-outline-warning btn-action" 
                                            onclick="editarReporte(<?= $row['id_repor'] ?>, '<?= addslashes($row['nombre']) ?>', '<?= addslashes($row['descripcion']) ?>', <?= $row['num_doc'] ?? 0 ?>, <?= $row['id_nit'] ?? 0 ?>, <?= $row['id_prod'] ?? 0 ?>)"
                                            title="Editar Reporte">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <button type="button" class="btn btn-outline-danger btn-action"
                                            onclick="confirmarEliminar(<?= $row['id_repor'] ?>, '<?= addslashes($row['nombre']) ?>')"
                                            title="Eliminar Reporte">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para crear reporte -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header gradient-bg">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Nuevo Reporte
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="nombre_reporte" class="form-label">
                                        <i class="fas fa-file-alt me-1"></i>Nombre del Reporte
                                    </label>
                                    <input type="text" name="nombre_reporte" id="nombre_reporte" class="form-control" 
                                           placeholder="Ej: Reporte mensual de inventario" required maxlength="255">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">
                                        <i class="fas fa-align-left me-1"></i>Descripción
                                    </label>
                                    <textarea name="descripcion" id="descripcion" class="form-control" rows="4" 
                                            placeholder="Describe el contenido y propósito del reporte..." required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="num_doc" class="form-label">
                                        <i class="fas fa-user me-1"></i>Usuario Responsable
                                    </label>
                                    <select name="num_doc" id="num_doc" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <?php 
                                        $users = $db->conn->query("SELECT num_doc, nombres FROM Users ORDER BY nombres");
                                        while($user = $users->fetch_assoc()): 
                                        ?>
                                        <option value="<?= $user['num_doc'] ?>">
                                            <?= htmlspecialchars($user['nombres']) ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="id_nit" class="form-label">
                                        <i class="fas fa-truck me-1"></i>Proveedor (opcional)
                                    </label>
                                    <select name="id_nit" id="id_nit" class="form-select">
                                        <option value="">Ninguno</option>
                                        <?php 
                                        $provs = $db->conn->query("SELECT id_nit, razon_social FROM Proveedores ORDER BY razon_social");
                                        while($prov = $provs->fetch_assoc()): 
                                        ?>
                                        <option value="<?= $prov['id_nit'] ?>">
                                            <?= htmlspecialchars($prov['razon_social']) ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="id_prod" class="form-label">
                                        <i class="fas fa-box me-1"></i>Producto (opcional)
                                    </label>
                                    <select name="id_prod" id="id_prod" class="form-select">
                                        <option value="">Ninguno</option>
                                        <?php 
                                        $prods = $db->conn->query("SELECT id_prod, nombre FROM Productos ORDER BY nombre");
                                        while($prod = $prods->fetch_assoc()): 
                                        ?>
                                        <option value="<?= $prod['id_prod'] ?>">
                                            <?= htmlspecialchars($prod['nombre']) ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_reporte" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Crear Reporte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar reporte -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #ffc107, #ff8f00); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Reporte
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="id_repor" id="editId">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="editNombreReporte" class="form-label">
                                        <i class="fas fa-file-alt me-1"></i>Nombre del Reporte
                                    </label>
                                    <input type="text" name="nombre_reporte" id="editNombreReporte" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="editDescripcion" class="form-label">
                                        <i class="fas fa-align-left me-1"></i>Descripción
                                    </label>
                                    <textarea name="descripcion" id="editDescripcion" class="form-control" rows="4" required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="editNumDoc" class="form-label">
                                        <i class="fas fa-user me-1"></i>Usuario Responsable
                                    </label>
                                    <select name="num_doc" id="editNumDoc" class="form-select" required>
                                        <!-- Se llenará dinámicamente -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="editIdNit" class="form-label">
                                        <i class="fas fa-truck me-1"></i>Proveedor
                                    </label>
                                    <select name="id_nit" id="editIdNit" class="form-select">
                                        <!-- Se llenará dinámicamente -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="editIdProd" class="form-label">
                                        <i class="fas fa-box me-1"></i>Producto
                                    </label>
                                    <select name="id_prod" id="editIdProd" class="form-select">
                                        <!-- Se llenará dinámicamente -->
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="modificar_reporte" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #17a2b8, #138496); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>Detalles del Reporte
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <h6><i class="fas fa-file-alt me-2"></i>Información del Reporte</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td id="detailNombre"></td>
                                </tr>
                                <tr>
                                    <td><strong>Descripción:</strong></td>
                                    <td id="detailDescripcion"></td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha:</strong></td>
                                    <td id="detailFecha"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <h6><i class="fas fa-user me-2"></i>Usuario</h6>
                            <p id="detailUsuario"></p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="fas fa-truck me-2"></i>Proveedor</h6>
                            <p id="detailProveedor"></p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="fas fa-box me-2"></i>Producto</h6>
                            <p id="detailProducto"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="fas fa-file-alt fa-3x text-danger mb-3"></i>
                        <p>¿Está seguro de que desea eliminar este reporte?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Reporte:</strong> <span id="reportName"></span>
                        </div>
                        <p class="text-muted">Esta acción no se puede deshacer.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fas fa-trash me-2"></i>Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let reportToDelete = null;
        
        // Datos para gráficos
        const reportesPorMesData = [
            <?php
            $meses = [];
            $cantidades = [];
            while($row = $reportes_por_mes->fetch_assoc()) {
                $meses[] = "'" . $row['mes'] . "'";
                $cantidades[] = $row['cantidad'];
            }
            echo implode(',', $cantidades);
            ?>
        ];
        
        const reportesPorUsuarioData = [
            <?php
            $usuarios_nombres = [];
            $usuarios_cantidades = [];
            while($row = $reportes_por_usuario->fetch_assoc()) {
                $usuarios_nombres[] = "'" . addslashes($row['nombres']) . "'";
                $usuarios_cantidades[] = $row['cantidad'];
            }
            echo implode(',', $usuarios_cantidades);
            ?>
        ];
        
        // Crear gráficos
        document.addEventListener('DOMContentLoaded', function() {
            // Gráfico de reportes por mes
            const ctx1 = document.getElementById('reportesPorMesChart').getContext('2d');
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: [<?= implode(',', $meses) ?>],
                    datasets: [{
                        label: 'Reportes por Mes',
                        data: reportesPorMesData,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Gráfico de reportes por usuario
            const ctx2 = document.getElementById('reportesPorUsuarioChart').getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: [<?= implode(',', $usuarios_nombres) ?>],
                    datasets: [{
                        data: reportesPorUsuarioData,
                        backgroundColor: [
                            '#667eea',
                            '#764ba2',
                            '#f093fb',
                            '#4facfe',
                            '#43e97b'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        });
        
        // Aplicar filtros
        function aplicarFiltros() {
            const usuario = document.getElementById('filtroUsuario').value;
            const proveedor = document.getElementById('filtroProveedor').value;
            const producto = document.getElementById('filtroProducto').value;
            const fechaDesde = document.getElementById('fechaDesde').value;
            const fechaHasta = document.getElementById('fechaHasta').value;
            
            let url = 'reportes.php?';
            if (usuario) url += 'usuario=' + encodeURIComponent(usuario) + '&';
            if (proveedor) url += 'proveedor=' + encodeURIComponent(proveedor) + '&';
            if (producto) url += 'producto=' + encodeURIComponent(producto) + '&';
            if (fechaDesde) url += 'fecha_desde=' + encodeURIComponent(fechaDesde) + '&';
            if (fechaHasta) url += 'fecha_hasta=' + encodeURIComponent(fechaHasta) + '&';
            
            window.location.href = url.slice(0, -1);
        }
        
        // Limpiar filtros
        function limpiarFiltros() {
            window.location.href = 'reportes.php';
        }
        
        // Ver detalles
        function verDetalles(id, nombre, descripcion, fecha, usuario, proveedor, producto) {
            document.getElementById('detailNombre').textContent = nombre;
            document.getElementById('detailDescripcion').textContent = descripcion;
            document.getElementById('detailFecha').textContent = new Date(fecha).toLocaleDateString('es-ES') + ' ' + new Date(fecha).toLocaleTimeString('es-ES');
            document.getElementById('detailUsuario').textContent = usuario || 'N/A';
            document.getElementById('detailProveedor').textContent = proveedor || 'N/A';
            document.getElementById('detailProducto').textContent = producto || 'N/A';
            
            new bootstrap.Modal(document.getElementById('detailsModal')).show();
        }
        
        // Editar reporte
        function editarReporte(id, nombre, descripcion, numDoc, idNit, idProd) {
            document.getElementById('editId').value = id;
            document.getElementById('editNombreReporte').value = nombre;
            document.getElementById('editDescripcion').value = descripcion;
            
            // Cargar opciones y seleccionar valores
            cargarOpcionesEdicion(numDoc, idNit, idProd);
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        // Cargar opciones para edición
        function cargarOpcionesEdicion(selectedUser, selectedProvider, selectedProduct) {
            // Aquí deberías cargar las opciones dinámicamente
            // Por simplicidad, usaremos las que ya están cargadas
            
            const userSelect = document.getElementById('editNumDoc');
            const providerSelect = document.getElementById('editIdNit');
            const productSelect = document.getElementById('editIdProd');
            
            // Limpiar opciones
            userSelect.innerHTML = '<option value="">Seleccione...</option>';
            providerSelect.innerHTML = '<option value="">Ninguno</option>';
            productSelect.innerHTML = '<option value="">Ninguno</option>';
            
            // Cargar usuarios
            <?php
            $users = $db->conn->query("SELECT num_doc, nombres FROM Users ORDER BY nombres");
            while($user = $users->fetch_assoc()): 
            ?>
            userSelect.innerHTML += '<option value="<?= $user['num_doc'] ?>"><?= addslashes($user['nombres']) ?></option>';
            <?php endwhile; ?>
            
            // Cargar proveedores
            <?php
            $provs = $db->conn->query("SELECT id_nit, razon_social FROM Proveedores ORDER BY razon_social");
            while($prov = $provs->fetch_assoc()): 
            ?>
            providerSelect.innerHTML += '<option value="<?= $prov['id_nit'] ?>"><?= addslashes($prov['razon_social']) ?></option>';
            <?php endwhile; ?>
            
            // Cargar productos
            <?php
            $prods = $db->conn->query("SELECT id_prod, nombre FROM Productos ORDER BY nombre");
            while($prod = $prods->fetch_assoc()): 
            ?>
            productSelect.innerHTML += '<option value="<?= $prod['id_prod'] ?>"><?= addslashes($prod['nombre']) ?></option>';
            <?php endwhile; ?>
            
            // Seleccionar valores actuales
            userSelect.value = selectedUser;
            providerSelect.value = selectedProvider;
            productSelect.value = selectedProduct;
        }
        
        // Confirmar eliminación
        function confirmarEliminar(id, nombre) {
            reportToDelete = id;
            document.getElementById('reportName').textContent = nombre;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Procesar eliminación
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (reportToDelete) {
                window.location.href = `reportes.php?eliminar=${reportToDelete}`;
            }
        });
        
        // Enter en filtros
        document.getElementById('filtroUsuario').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                aplicarFiltros();
            }
        });
        
        // Animaciones al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.animate-fade-in');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
            
            // Establecer fecha actual como máximo
            const fechaHoy = new Date().toISOString().split('T')[0];
            document.getElementById('fechaDesde').max = fechaHoy;
            document.getElementById('fechaHasta').max = fechaHoy;
        });
    </script>
</body>
</html>