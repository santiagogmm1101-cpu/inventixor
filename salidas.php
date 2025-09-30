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

// Eliminar salida (restaurar stock)
if (isset($_GET['eliminar']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'coordinador')) {
    $id_salida = intval($_GET['eliminar']);
    
    // Obtener datos de la salida
    $stmt = $db->conn->prepare("SELECT id_prod, cantidad FROM Salidas WHERE id_salida = ?");
    $stmt->bind_param('i', $id_salida);
    $stmt->execute();
    $result = $stmt->get_result();
    $salida = $result->fetch_assoc();
    $stmt->close();
    
    if ($salida) {
        // Eliminar salida
        $stmt2 = $db->conn->prepare("DELETE FROM Salidas WHERE id_salida = ?");
        $stmt2->bind_param('i', $id_salida);
        $success = $stmt2->execute();
        $stmt2->close();
        
        if ($success) {
            // Restaurar stock
            $stmt3 = $db->conn->prepare("UPDATE Productos SET stock = stock + ? WHERE id_prod = ?");
            $stmt3->bind_param('ii', $salida['cantidad'], $salida['id_prod']);
            $stmt3->execute();
            $stmt3->close();
            
            header('Location: salidas.php?msg=eliminado');
            exit;
        } else {
            $error = "No se pudo eliminar la salida.";
        }
    }
}

// Solicitud de retorno a inventario (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar_retorno'])) {
    header('Content-Type: application/json');
    $salida_id = intval($_POST['salida_id'] ?? 0);
    $observacion = trim($_POST['motivo'] ?? '');
    $usuario = $_SESSION['user']['nombres'] ?? 'Desconocido';
    
    if ($salida_id && $observacion) {
        // Obtener salida y producto
        $stmt = $db->conn->prepare("SELECT s.*, p.nombre FROM Salidas s JOIN Productos p ON s.id_prod = p.id_prod WHERE s.id_salida = ?");
        $stmt->bind_param('i', $salida_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $salida = $result->fetch_assoc();
        $stmt->close();
        
        if ($salida) {
            // Registrar en alertas
            $stmt = $db->conn->prepare("INSERT INTO Alertas (id_prod, tipo, mensaje, fecha_hora) VALUES (?, 'retorno', ?, NOW())");
            $mensaje = "Solicitud de retorno: {$salida['nombre']} - Motivo: $observacion - Usuario: $usuario";
            $stmt->bind_param('is', $salida['id_prod'], $mensaje);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode(['success' => true]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// Registrar nueva salida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_salida'])) {
    $producto_id = intval($_POST['producto_id']);
    $cantidad = intval($_POST['cantidad']);
    $observacion = $_POST['motivo'] ?? '';
    $tipo_salida = $_POST['tipo_salida'] ?? 'Venta';
    
    if ($producto_id && $cantidad > 0) {
        // Verificar stock disponible
        $stmt = $db->conn->prepare("SELECT id_prod, nombre, stock, id_nit FROM Productos WHERE id_prod = ?");
        $stmt->bind_param('i', $producto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $producto = $result->fetch_assoc();
        $stmt->close();
        
        if (!$producto) {
            $error = 'Producto no encontrado.';
        } elseif ($producto['stock'] < $cantidad) {
            $error = "Stock insuficiente. Disponible: {$producto['stock']}, Solicitado: $cantidad";
        } else {
            // Registrar salida
            $stmt = $db->conn->prepare("INSERT INTO Salidas (id_prod, cantidad, tipo_salida, observacion, fecha_hora) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param('iiss', $producto_id, $cantidad, $tipo_salida, $observacion);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                // Actualizar stock
                $stmt2 = $db->conn->prepare("UPDATE Productos SET stock = stock - ? WHERE id_prod = ?");
                $stmt2->bind_param('ii', $cantidad, $producto_id);
                $stmt2->execute();
                $stmt2->close();
                
                // Generar reporte automático
                $descripcion = "Salida de {$producto['nombre']} - Cantidad: $cantidad - Tipo: $tipo_salida - Observación: $observacion";
                $num_doc = $_SESSION['user']['num_doc'] ?? null;
                
                $stmt3 = $db->conn->prepare("INSERT INTO Reportes (nombre_reporte, descripcion, num_doc, id_nit, id_prod, fecha_reporte) VALUES (?, ?, ?, ?, ?, NOW())");
                $nombre_reporte = 'Salida de Producto';
                $stmt3->bind_param('sssii', $nombre_reporte, $descripcion, $num_doc, $producto['id_nit'], $producto_id);
                $stmt3->execute();
                $stmt3->close();
                
                header('Location: salidas.php?msg=registrado');
                exit;
            } else {
                $error = 'Error al registrar la salida.';
            }
        }
    } else {
        $error = 'Datos incompletos o inválidos.';
    }
}

// Mensajes
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'registrado':
            $message = 'Salida registrada correctamente y stock actualizado.';
            break;
        case 'eliminado':
            $message = 'Salida eliminada y stock restaurado.';
            break;
    }
}

// Filtros
$filtro_producto = $_GET['producto'] ?? '';
$filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Consulta con JOIN mejorado
$sql = "SELECT s.id_salida, s.cantidad, s.tipo_salida, s.observacion, s.fecha_hora,
               p.id_prod, p.nombre as producto_nombre, p.stock as stock_actual,
               sc.nombre as subcategoria_nombre,
               c.nombre as categoria_nombre,
               pr.razon_social as proveedor_nombre,
               u.nombres as usuario_nombres
        FROM Salidas s
        INNER JOIN Productos p ON s.id_prod = p.id_prod
        LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
        LEFT JOIN Categoria c ON sc.id_categ = c.id_categ
        LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit
        LEFT JOIN Users u ON p.num_doc = u.num_doc";

$where_conditions = [];
$params = [];
$types = '';

if ($filtro_producto) {
    $where_conditions[] = "p.nombre LIKE ?";
    $params[] = "%$filtro_producto%";
    $types .= 's';
}

if ($filtro_fecha_desde) {
    $where_conditions[] = "DATE(s.fecha_hora) >= ?";
    $params[] = $filtro_fecha_desde;
    $types .= 's';
}

if ($filtro_fecha_hasta) {
    $where_conditions[] = "DATE(s.fecha_hora) <= ?";
    $params[] = $filtro_fecha_hasta;
    $types .= 's';
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " ORDER BY s.fecha_hora DESC";

if (!empty($params)) {
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $salidas = $stmt->get_result();
    $stmt->close();
} else {
    $salidas = $db->conn->query($sql);
}

// Obtener productos para el select (solo con stock > 0)
$productos = $db->conn->query("SELECT p.id_prod, p.nombre, p.stock, pr.razon_social 
                              FROM Productos p 
                              LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit 
                              WHERE p.stock > 0 
                              ORDER BY p.nombre");

// Estadísticas
$stats = $db->conn->query("SELECT 
    COUNT(*) as total_salidas,
    SUM(cantidad) as total_cantidad,
    COUNT(DISTINCT id_prod) as productos_diferentes,
    DATE(MAX(fecha_hora)) as ultima_salida
    FROM Salidas 
    WHERE DATE(fecha_hora) = CURDATE()")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Salidas - Inventixor</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
        
        .filter-card, .form-card {
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
        
        .stock-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
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
                <a href="salidas.php" class="menu-link active">
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
        <!-- Header -->
        <div class="main-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-sign-out-alt me-2"></i>Gestión de Salidas</h2>
                        <p class="mb-0">Control de salidas de productos con descuento automático de stock</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-light text-dark">
                            Rol: <?= htmlspecialchars($_SESSION['rol']??'') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4 animate-fade-in">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon text-primary">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stats-number text-primary"><?= $stats['total_salidas'] ?? 0 ?></div>
                    <div class="text-muted">Salidas Hoy</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon text-info">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stats-number text-info"><?= $stats['total_cantidad'] ?? 0 ?></div>
                    <div class="text-muted">Cantidad Total</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon text-warning">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stats-number text-warning"><?= $stats['productos_diferentes'] ?? 0 ?></div>
                    <div class="text-muted">Productos Diferentes</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon text-success">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-number text-success">
                        <?= $stats['ultima_salida'] ? date('H:i', strtotime($stats['ultima_salida'])) : '--:--' ?>
                    </div>
                    <div class="text-muted">Última Salida</div>
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

        <!-- Formulario de Registro -->
        <div class="form-card animate-fade-in">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-plus-circle me-2"></i>Registrar Nueva Salida</h5>
            </div>
            <form method="POST">
                <input type="hidden" name="registrar_salida" value="1">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label for="producto_id" class="form-label">
                            <i class="fas fa-box me-1"></i>Producto
                        </label>
                        <select name="producto_id" id="producto_id" class="form-select" required onchange="updateStock()">
                            <option value="">Seleccione un producto...</option>
                            <?php while($producto = $productos->fetch_assoc()): ?>
                            <option value="<?= $producto['id_prod'] ?>" data-stock="<?= $producto['stock'] ?>">
                                <?= htmlspecialchars($producto['nombre']) ?> 
                                (Stock: <?= $producto['stock'] ?>) 
                                - <?= htmlspecialchars($producto['razon_social'] ?? 'Sin proveedor') ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="cantidad" class="form-label">
                            <i class="fas fa-hashtag me-1"></i>Cantidad
                        </label>
                        <input type="number" name="cantidad" id="cantidad" class="form-control" 
                               min="1" required placeholder="0" onchange="validateStock()">
                        <small class="text-muted">Stock disponible: <span id="stockDisplay">--</span></small>
                    </div>
                    <div class="col-md-4">
                        <label for="motivo" class="form-label">
                            <i class="fas fa-comment me-1"></i>Motivo (opcional)
                        </label>
                        <input type="text" name="motivo" id="motivo" class="form-control" 
                               placeholder="Ej: Venta, devolución, daño...">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success w-100" id="submitBtn">
                            <i class="fas fa-save me-2"></i>Registrar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Filtros -->
        <div class="filter-card animate-fade-in">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-filter me-2"></i>Filtros de Búsqueda</h5>
            </div>
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="filtroProducto" class="form-label">Buscar por producto:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="filtroProducto" class="form-control" 
                               placeholder="Nombre del producto..." value="<?= htmlspecialchars($filtro_producto) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="fechaDesde" class="form-label">Desde:</label>
                    <input type="date" id="fechaDesde" class="form-control" value="<?= htmlspecialchars($filtro_fecha_desde) ?>">
                </div>
                <div class="col-md-3">
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

        <!-- Tabla de Salidas -->
        <div class="table-card animate-fade-in">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="gradient-bg">
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                            <th><i class="fas fa-box me-1"></i>Producto</th>
                            <th><i class="fas fa-layer-group me-1"></i>Categoría</th>
                            <th><i class="fas fa-truck me-1"></i>Proveedor</th>
                            <th><i class="fas fa-sort-numeric-down me-1"></i>Cantidad</th>
                            <th><i class="fas fa-boxes me-1"></i>Stock Actual</th>
                            <th><i class="fas fa-comment me-1"></i>Motivo</th>
                            <th><i class="fas fa-calendar me-1"></i>Fecha</th>
                            <th><i class="fas fa-user me-1"></i>Usuario</th>
                            <th><i class="fas fa-cogs me-1"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($row = $salidas->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge bg-primary"><?= $row['id_salida'] ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($row['producto_nombre']) ?></strong>
                                <small class="d-block text-muted">ID: <?= $row['id_prod'] ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= htmlspecialchars($row['categoria_nombre'] ?? 'N/A') ?></span>
                                <small class="d-block text-muted"><?= htmlspecialchars($row['subcategoria_nombre'] ?? 'N/A') ?></small>
                            </td>
                            <td><?= htmlspecialchars($row['proveedor_nombre'] ?? 'Sin proveedor') ?></td>
                            <td>
                                <span class="badge bg-warning text-dark fs-6"><?= $row['cantidad'] ?></span>
                            </td>
                            <td>
                                <?php
                                $stockClass = '';
                                if ($row['stock_actual'] == 0) $stockClass = 'bg-danger';
                                elseif ($row['stock_actual'] <= 10) $stockClass = 'bg-warning';
                                else $stockClass = 'bg-success';
                                ?>
                                <span class="badge <?= $stockClass ?> stock-badge"><?= $row['stock_actual'] ?></span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($row['tipo_salida'] ?: 'Sin especificar') ?></strong>
                                <?php if ($row['observacion']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($row['observacion']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small>
                                    <?= date('d/m/Y', strtotime($row['fecha_hora'])) ?><br>
                                    <?= date('H:i:s', strtotime($row['fecha_hora'])) ?>
                                </small>
                            </td>
                            <td><?= htmlspecialchars($row['usuario_nombres'] ?? 'Sistema') ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if ($_SESSION['rol'] === 'auxiliar'): ?>
                                    <button type="button" class="btn btn-outline-info btn-action" 
                                            onclick="solicitudRetorno(<?= $row['id_salida'] ?>, '<?= addslashes($row['producto_nombre']) ?>')"
                                            title="Solicitar Retorno">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-outline-danger btn-action"
                                            onclick="confirmarEliminar(<?= $row['id_salida'] ?>, '<?= addslashes($row['producto_nombre']) ?>', <?= $row['cantidad'] ?>)"
                                            title="Eliminar y Restaurar Stock">
                                        <i class="fas fa-trash-restore"></i>
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

    <!-- Modal para solicitud de retorno -->
    <div class="modal fade" id="retornoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-undo me-2"></i>Solicitar Retorno a Inventario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="retornoForm">
                        <input type="hidden" id="retornoSalidaId">
                        <div class="mb-3">
                            <label for="retornoProducto" class="form-label">
                                <i class="fas fa-box me-1"></i>Producto
                            </label>
                            <input type="text" id="retornoProducto" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="retornoMotivo" class="form-label">
                                <i class="fas fa-comment me-1"></i>Motivo del Retorno
                            </label>
                            <textarea id="retornoMotivo" class="form-control" rows="3" required
                                    placeholder="Explique el motivo del retorno (defecto, error, devolución, etc.)"></textarea>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Esta solicitud será enviada al coordinador y administrador para su aprobación.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-info" onclick="enviarRetorno()">
                        <i class="fas fa-paper-plane me-2"></i>Enviar Solicitud
                    </button>
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
                        <i class="fas fa-trash-restore fa-3x text-danger mb-3"></i>
                        <p>¿Está seguro de eliminar esta salida?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Producto:</strong> <span id="deleteProductName"></span><br>
                            <strong>Cantidad:</strong> <span id="deleteCantidad"></span> unidades
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-undo me-2"></i>
                            El stock será <strong>restaurado automáticamente</strong> al inventario.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fas fa-trash-restore me-2"></i>Eliminar y Restaurar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let salidaToDelete = null;
        
        // Actualizar stock disponible
        function updateStock() {
            const select = document.getElementById('producto_id');
            const stockDisplay = document.getElementById('stockDisplay');
            const cantidadInput = document.getElementById('cantidad');
            
            if (select.value) {
                const stock = select.options[select.selectedIndex].dataset.stock;
                stockDisplay.textContent = stock;
                cantidadInput.max = stock;
                cantidadInput.value = '';
            } else {
                stockDisplay.textContent = '--';
                cantidadInput.max = '';
            }
            validateStock();
        }
        
        // Validar cantidad vs stock
        function validateStock() {
            const select = document.getElementById('producto_id');
            const cantidadInput = document.getElementById('cantidad');
            const submitBtn = document.getElementById('submitBtn');
            
            if (select.value && cantidadInput.value) {
                const stock = parseInt(select.options[select.selectedIndex].dataset.stock);
                const cantidad = parseInt(cantidadInput.value);
                
                if (cantidad > stock) {
                    cantidadInput.classList.add('is-invalid');
                    submitBtn.disabled = true;
                } else {
                    cantidadInput.classList.remove('is-invalid');
                    submitBtn.disabled = false;
                }
            } else {
                cantidadInput.classList.remove('is-invalid');
                submitBtn.disabled = false;
            }
        }
        
        // Aplicar filtros
        function aplicarFiltros() {
            const producto = document.getElementById('filtroProducto').value;
            const fechaDesde = document.getElementById('fechaDesde').value;
            const fechaHasta = document.getElementById('fechaHasta').value;
            
            let url = 'salidas.php?';
            if (producto) url += 'producto=' + encodeURIComponent(producto) + '&';
            if (fechaDesde) url += 'fecha_desde=' + encodeURIComponent(fechaDesde) + '&';
            if (fechaHasta) url += 'fecha_hasta=' + encodeURIComponent(fechaHasta) + '&';
            
            window.location.href = url.slice(0, -1);
        }
        
        // Limpiar filtros
        function limpiarFiltros() {
            window.location.href = 'salidas.php';
        }
        
        // Solicitud de retorno
        function solicitudRetorno(salidaId, productoNombre) {
            document.getElementById('retornoSalidaId').value = salidaId;
            document.getElementById('retornoProducto').value = productoNombre;
            document.getElementById('retornoMotivo').value = '';
            new bootstrap.Modal(document.getElementById('retornoModal')).show();
        }
        
        // Enviar retorno
        function enviarRetorno() {
            const salidaId = document.getElementById('retornoSalidaId').value;
            const motivo = document.getElementById('retornoMotivo').value;
            
            if (!motivo.trim()) {
                alert('Debe especificar el motivo del retorno.');
                return;
            }
            
            const formData = new FormData();
            formData.append('solicitar_retorno', '1');
            formData.append('salida_id', salidaId);
            formData.append('motivo', motivo);
            
            fetch('salidas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Solicitud enviada correctamente a coordinadores y administradores.');
                    bootstrap.Modal.getInstance(document.getElementById('retornoModal')).hide();
                } else {
                    alert('Error: ' + (data.error || 'No se pudo enviar la solicitud.'));
                }
            })
            .catch(() => alert('Error de red al enviar la solicitud.'));
        }
        
        // Confirmar eliminación
        function confirmarEliminar(id, productoNombre, cantidad) {
            salidaToDelete = id;
            document.getElementById('deleteProductName').textContent = productoNombre;
            document.getElementById('deleteCantidad').textContent = cantidad;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Procesar eliminación
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (salidaToDelete) {
                window.location.href = `salidas.php?eliminar=${salidaToDelete}`;
            }
        });
        
        // Enter en filtros
        document.getElementById('filtroProducto').addEventListener('keypress', function(e) {
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