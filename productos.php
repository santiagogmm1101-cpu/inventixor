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

// Eliminar producto
if (isset($_GET['eliminar']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'coordinador')) {
    $id_prod = intval($_GET['eliminar']);
    
    // Verificar relaciones
    $salidas = $db->conn->query("SELECT COUNT(*) FROM Salidas WHERE id_prod = $id_prod")->fetch_row()[0];
    $alertas = $db->conn->query("SELECT COUNT(*) FROM Alertas WHERE id_prod = $id_prod")->fetch_row()[0];
    $reportes = $db->conn->query("SELECT COUNT(*) FROM Reportes WHERE id_prod = $id_prod")->fetch_row()[0];
    
    if ($salidas > 0 || $alertas > 0 || $reportes > 0) {
        $entidades = [];
        if ($salidas > 0) $entidades[] = "salidas ($salidas)";
        if ($alertas > 0) $entidades[] = "alertas ($alertas)";
        if ($reportes > 0) $entidades[] = "reportes ($reportes)";
        $error = "No se puede eliminar el producto porque tiene " . implode(', ', $entidades) . " asociadas.";
    } else {
        $stmt = $db->conn->prepare("DELETE FROM Productos WHERE id_prod = ?");
        $stmt->bind_param('i', $id_prod);
        $stmt->execute();
        $stmt->close();
        header('Location: productos.php?msg=eliminado');
        exit;
    }
}

// Modificar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modificar_producto'])) {
    if (in_array($_SESSION['rol'], ['admin', 'coordinador', 'auxiliar'])) {
        $id_prod = intval($_POST['id_prod']);
        $nombre = $_POST['nombre'];
        $modelo = $_POST['modelo'];
        $talla = $_POST['talla'];
        $color = $_POST['color'];
        $stock = intval($_POST['stock']);
        $fecha_ing = $_POST['fecha_ing'];
        $material = $_POST['material'];
        $id_subcat = intval($_POST['id_subcat']);
        $id_nit = intval($_POST['id_nit']);
        $num_doc = intval($_POST['num_doc']);
        
        $stmt = $db->conn->prepare("UPDATE Productos SET nombre=?, modelo=?, talla=?, color=?, stock=?, fecha_ing=?, material=?, id_subcat=?, id_nit=?, num_doc=? WHERE id_prod=?");
        $stmt->bind_param('ssssissiiii', $nombre, $modelo, $talla, $color, $stock, $fecha_ing, $material, $id_subcat, $id_nit, $num_doc, $id_prod);
        $stmt->execute();
        $stmt->close();
        header('Location: productos.php?msg=modificado');
        exit;
    } else {
        $error = 'No tienes permisos para modificar productos.';
    }
}

// Crear producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_producto'])) {
    if (in_array($_SESSION['rol'], ['admin', 'coordinador', 'auxiliar'])) {
        $nombre = $_POST['nombre'];
        $modelo = $_POST['modelo'];
        $talla = $_POST['talla'];
        $color = $_POST['color'];
        $stock = intval($_POST['stock']);
        $fecha_ing = $_POST['fecha_ing'];
        $material = $_POST['material'];
        $id_subcat = intval($_POST['id_subcat']);
        $id_nit = intval($_POST['id_nit']);
        $num_doc = intval($_POST['num_doc']);
        
        $stmt = $db->conn->prepare("INSERT INTO Productos (nombre, modelo, talla, color, stock, fecha_ing, material, id_subcat, id_nit, num_doc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssissiii', $nombre, $modelo, $talla, $color, $stock, $fecha_ing, $material, $id_subcat, $id_nit, $num_doc);
        $stmt->execute();
        $stmt->close();
        header('Location: productos.php?msg=creado');
        exit;
    } else {
        $error = 'No tienes permisos para crear productos.';
    }
}

// Mensajes
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'creado':
            $message = 'Producto creado correctamente.';
            break;
        case 'modificado':
            $message = 'Producto modificado correctamente.';
            break;
        case 'eliminado':
            $message = 'Producto eliminado correctamente.';
            break;
    }
}

// Filtros
$filtro_nombre = $_GET['nombre'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_proveedor = $_GET['proveedor'] ?? '';
$filtro_stock = $_GET['stock'] ?? '';
$filtro_bajo_stock = isset($_GET['bajo_stock']);

// Consulta con JOIN completo
$sql = "SELECT p.id_prod, p.nombre, p.modelo, p.talla, p.color, p.stock, p.fecha_ing, p.material,
               sc.id_subcg, sc.nombre as subcategoria_nombre,
               c.id_categ, c.nombre as categoria_nombre,
               pr.id_nit, pr.razon_social as proveedor_nombre,
               u.num_doc, u.nombres as usuario_nombre,
               COUNT(DISTINCT s.id_salida) as total_salidas,
               COUNT(DISTINCT a.id_alerta) as total_alertas
        FROM Productos p
        LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
        LEFT JOIN Categoria c ON sc.id_categ = c.id_categ
        LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit
        LEFT JOIN Users u ON p.num_doc = u.num_doc
        LEFT JOIN Salidas s ON p.id_prod = s.id_prod
        LEFT JOIN Alertas a ON p.id_prod = a.id_prod";

$where_conditions = [];
$params = [];
$types = '';

if ($filtro_nombre) {
    $where_conditions[] = "p.nombre LIKE ?";
    $params[] = "%$filtro_nombre%";
    $types .= 's';
}

if ($filtro_categoria) {
    $where_conditions[] = "c.id_categ = ?";
    $params[] = $filtro_categoria;
    $types .= 'i';
}

if ($filtro_proveedor) {
    $where_conditions[] = "pr.id_nit = ?";
    $params[] = $filtro_proveedor;
    $types .= 'i';
}

if ($filtro_stock) {
    $where_conditions[] = "p.stock <= ?";
    $params[] = $filtro_stock;
    $types .= 'i';
}

if ($filtro_bajo_stock) {
    $where_conditions[] = "p.stock <= 10";
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " GROUP BY p.id_prod ORDER BY p.nombre";

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
$categorias = $db->conn->query("SELECT id_categ, nombre FROM Categoria ORDER BY nombre");
$subcategorias = $db->conn->query("SELECT sc.id_subcg, sc.nombre, c.nombre as categoria_nombre 
                                 FROM Subcategoria sc 
                                 JOIN Categoria c ON sc.id_categ = c.id_categ 
                                 ORDER BY c.nombre, sc.nombre");
$proveedores = $db->conn->query("SELECT id_nit, razon_social FROM Proveedores ORDER BY razon_social");
$usuarios = $db->conn->query("SELECT num_doc, nombres FROM Users ORDER BY nombres");

// Estadísticas
$stats = $db->conn->query("SELECT 
    COUNT(*) as total_productos,
    SUM(stock) as stock_total,
    COUNT(CASE WHEN stock <= 10 THEN 1 END) as productos_bajo_stock,
    COUNT(CASE WHEN stock = 0 THEN 1 END) as productos_sin_stock
    FROM Productos")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Inventixor</title>
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
            font-size: 0.9rem;
            padding: 0.35rem 0.7rem;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .product-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .product-card:hover {
            transform: translateY(-2px);
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
                <a href="productos.php" class="menu-link active">
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
        <!-- Header -->
        <div class="main-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-box me-2"></i>Gestión de Productos</h2>
                        <p class="mb-0">Administra el catálogo completo de productos del inventario</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-light text-dark">
                            Rol: <?= htmlspecialchars($_SESSION['rol']??'') ?>
                        </span>
                        <?php if (in_array($_SESSION['rol'], ['admin', 'coordinador', 'auxiliar'])): ?>
                        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#createModal">
                            <i class="fas fa-plus me-2"></i>Nuevo Producto
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
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stats-number text-primary"><?= $stats['total_productos'] ?></div>
                    <div class="text-muted">Total Productos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon text-info">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stats-number text-info"><?= number_format($stats['stock_total']) ?></div>
                    <div class="text-muted">Stock Total</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon text-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stats-number text-warning"><?= $stats['productos_bajo_stock'] ?></div>
                    <div class="text-muted">Stock Bajo (≤10)</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stats-number text-danger"><?= $stats['productos_sin_stock'] ?></div>
                    <div class="text-muted">Sin Stock</div>
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

        <!-- Filtros -->
        <div class="filter-card animate-fade-in">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-filter me-2"></i>Filtros y Búsqueda</h5>
                <a href="reportes.php?tabla=productos" class="btn btn-outline-primary">
                    <i class="fas fa-chart-line me-2"></i>Ver Reportes
                </a>
            </div>
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label for="filtroNombre" class="form-label">Buscar por nombre:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="filtroNombre" class="form-control" 
                               placeholder="Nombre del producto..." value="<?= htmlspecialchars($filtro_nombre) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label for="filtroCategoria" class="form-label">Categoría:</label>
                    <select id="filtroCategoria" class="form-select">
                        <option value="">Todas</option>
                        <?php while($cat = $categorias->fetch_assoc()): ?>
                        <option value="<?= $cat['id_categ'] ?>" <?= $filtro_categoria == $cat['id_categ'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nombre']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
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
                    <label for="filtroStock" class="form-label">Stock máximo:</label>
                    <input type="number" id="filtroStock" class="form-control" 
                           placeholder="Ej: 50" value="<?= htmlspecialchars($filtro_stock) ?>">
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="aplicarFiltros()">
                            <i class="fas fa-search me-1"></i>Filtrar
                        </button>
                        <button class="btn btn-warning" onclick="filtrarBajoStock()">
                            <i class="fas fa-exclamation-triangle me-1"></i>Stock Bajo
                        </button>
                        <button class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                            <i class="fas fa-times me-1"></i>Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Productos -->
        <div class="table-card animate-fade-in">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="gradient-bg">
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                            <th><i class="fas fa-box me-1"></i>Producto</th>
                            <th><i class="fas fa-info me-1"></i>Detalles</th>
                            <th><i class="fas fa-tags me-1"></i>Categoría</th>
                            <th><i class="fas fa-truck me-1"></i>Proveedor</th>
                            <th><i class="fas fa-layer-group me-1"></i>Stock</th>
                            <th><i class="fas fa-calendar me-1"></i>Fecha Ingreso</th>
                            <th><i class="fas fa-chart-line me-1"></i>Actividad</th>
                            <th><i class="fas fa-cogs me-1"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge bg-primary"><?= $row['id_prod'] ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($row['nombre']) ?></strong>
                                <small class="d-block text-muted">Modelo: <?= htmlspecialchars($row['modelo']) ?></small>
                            </td>
                            <td>
                                <small>
                                    <i class="fas fa-resize-arrows-alt me-1"></i>Talla: <?= htmlspecialchars($row['talla']) ?><br>
                                    <i class="fas fa-palette me-1"></i>Color: <?= htmlspecialchars($row['color']) ?><br>
                                    <i class="fas fa-cube me-1"></i>Material: <?= htmlspecialchars($row['material']) ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= htmlspecialchars($row['categoria_nombre'] ?? 'N/A') ?></span>
                                <small class="d-block text-muted"><?= htmlspecialchars($row['subcategoria_nombre'] ?? 'N/A') ?></small>
                            </td>
                            <td><?= htmlspecialchars($row['proveedor_nombre'] ?? 'Sin proveedor') ?></td>
                            <td>
                                <?php
                                $stockClass = '';
                                if ($row['stock'] == 0) $stockClass = 'bg-danger';
                                elseif ($row['stock'] <= 10) $stockClass = 'bg-warning';
                                else $stockClass = 'bg-success';
                                ?>
                                <span class="badge <?= $stockClass ?> stock-badge"><?= $row['stock'] ?></span>
                            </td>
                            <td>
                                <small><?= date('d/m/Y', strtotime($row['fecha_ing'])) ?></small>
                            </td>
                            <td>
                                <small>
                                    <i class="fas fa-sign-out-alt me-1"></i>
                                    <span class="badge bg-secondary"><?= $row['total_salidas'] ?></span> salidas<br>
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <span class="badge bg-warning"><?= $row['total_alertas'] ?></span> alertas
                                </small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-info btn-action" 
                                            onclick="verDetalles(<?= $row['id_prod'] ?>, '<?= addslashes($row['nombre']) ?>', '<?= addslashes($row['modelo']) ?>', '<?= addslashes($row['talla']) ?>', '<?= addslashes($row['color']) ?>', <?= $row['stock'] ?>, '<?= $row['fecha_ing'] ?>', '<?= addslashes($row['material']) ?>', '<?= addslashes($row['categoria_nombre'] ?? '') ?>', '<?= addslashes($row['subcategoria_nombre'] ?? '') ?>', '<?= addslashes($row['proveedor_nombre'] ?? '') ?>', '<?= addslashes($row['usuario_nombre'] ?? '') ?>')"
                                            title="Ver Detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if (in_array($_SESSION['rol'], ['admin', 'coordinador', 'auxiliar'])): ?>
                                    <button type="button" class="btn btn-outline-warning btn-action" 
                                            onclick="editarProducto(<?= $row['id_prod'] ?>, '<?= addslashes($row['nombre']) ?>', '<?= addslashes($row['modelo']) ?>', '<?= addslashes($row['talla']) ?>', '<?= addslashes($row['color']) ?>', <?= $row['stock'] ?>, '<?= $row['fecha_ing'] ?>', '<?= addslashes($row['material']) ?>', <?= $row['id_subcat'] ?? 0 ?>, <?= $row['id_nit'] ?? 0 ?>, <?= $row['num_doc'] ?? 0 ?>)"
                                            title="Editar Producto">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($_SESSION['rol'], ['admin', 'coordinador'])): ?>
                                    <button type="button" class="btn btn-outline-danger btn-action"
                                            onclick="confirmarEliminar(<?= $row['id_prod'] ?>, '<?= addslashes($row['nombre']) ?>')"
                                            title="Eliminar Producto">
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

    <!-- Modal para crear producto -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header gradient-bg">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Nuevo Producto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">
                                        <i class="fas fa-box me-1"></i>Nombre del Producto
                                    </label>
                                    <input type="text" name="nombre" id="nombre" class="form-control" 
                                           placeholder="Ej: Camiseta Polo" required maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="modelo" class="form-label">
                                        <i class="fas fa-barcode me-1"></i>Modelo
                                    </label>
                                    <input type="text" name="modelo" id="modelo" class="form-control" 
                                           placeholder="Ej: POL-001" required maxlength="50">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="talla" class="form-label">
                                        <i class="fas fa-resize-arrows-alt me-1"></i>Talla
                                    </label>
                                    <input type="text" name="talla" id="talla" class="form-control" 
                                           placeholder="Ej: M, L, XL" required maxlength="20">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="color" class="form-label">
                                        <i class="fas fa-palette me-1"></i>Color
                                    </label>
                                    <input type="text" name="color" id="color" class="form-control" 
                                           placeholder="Ej: Azul" required maxlength="30">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="stock" class="form-label">
                                        <i class="fas fa-layer-group me-1"></i>Stock Inicial
                                    </label>
                                    <input type="number" name="stock" id="stock" class="form-control" 
                                           placeholder="Ej: 100" required min="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_ing" class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Fecha de Ingreso
                                    </label>
                                    <input type="date" name="fecha_ing" id="fecha_ing" class="form-control" 
                                           value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="material" class="form-label">
                                        <i class="fas fa-cube me-1"></i>Material
                                    </label>
                                    <input type="text" name="material" id="material" class="form-control" 
                                           placeholder="Ej: Algodón 100%" required maxlength="100">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="id_subcat" class="form-label">
                                        <i class="fas fa-tag me-1"></i>Subcategoría
                                    </label>
                                    <select name="id_subcat" id="id_subcat" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <?php 
                                        $subcats = $db->conn->query("SELECT sc.id_subcg, sc.nombre, c.nombre as categoria_nombre 
                                                                   FROM Subcategoria sc 
                                                                   JOIN Categoria c ON sc.id_categ = c.id_categ 
                                                                   ORDER BY c.nombre, sc.nombre");
                                        while($subcat = $subcats->fetch_assoc()): 
                                        ?>
                                        <option value="<?= $subcat['id_subcg'] ?>">
                                            <?= htmlspecialchars($subcat['categoria_nombre']) ?> - <?= htmlspecialchars($subcat['nombre']) ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="id_nit" class="form-label">
                                        <i class="fas fa-truck me-1"></i>Proveedor
                                    </label>
                                    <select name="id_nit" id="id_nit" class="form-select" required>
                                        <option value="">Seleccione...</option>
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
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_producto" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Guardar Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar producto -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #ffc107, #ff8f00); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Producto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="id_prod" id="editId">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editNombre" class="form-label">
                                        <i class="fas fa-box me-1"></i>Nombre del Producto
                                    </label>
                                    <input type="text" name="nombre" id="editNombre" class="form-control" required maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editModelo" class="form-label">
                                        <i class="fas fa-barcode me-1"></i>Modelo
                                    </label>
                                    <input type="text" name="modelo" id="editModelo" class="form-control" required maxlength="50">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="editTalla" class="form-label">
                                        <i class="fas fa-resize-arrows-alt me-1"></i>Talla
                                    </label>
                                    <input type="text" name="talla" id="editTalla" class="form-control" required maxlength="20">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="editColor" class="form-label">
                                        <i class="fas fa-palette me-1"></i>Color
                                    </label>
                                    <input type="text" name="color" id="editColor" class="form-control" required maxlength="30">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="editStock" class="form-label">
                                        <i class="fas fa-layer-group me-1"></i>Stock
                                    </label>
                                    <input type="number" name="stock" id="editStock" class="form-control" required min="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editFechaIng" class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Fecha de Ingreso
                                    </label>
                                    <input type="date" name="fecha_ing" id="editFechaIng" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editMaterial" class="form-label">
                                        <i class="fas fa-cube me-1"></i>Material
                                    </label>
                                    <input type="text" name="material" id="editMaterial" class="form-control" required maxlength="100">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="editSubcat" class="form-label">
                                        <i class="fas fa-tag me-1"></i>Subcategoría
                                    </label>
                                    <select name="id_subcat" id="editSubcat" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <!-- Se llenará dinámicamente -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="editProveedor" class="form-label">
                                        <i class="fas fa-truck me-1"></i>Proveedor
                                    </label>
                                    <select name="id_nit" id="editProveedor" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <!-- Se llenará dinámicamente -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="editUsuario" class="form-label">
                                        <i class="fas fa-user me-1"></i>Usuario Responsable
                                    </label>
                                    <select name="num_doc" id="editUsuario" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <!-- Se llenará dinámicamente -->
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="modificar_producto" class="btn btn-warning">
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
                        <i class="fas fa-eye me-2"></i>Detalles del Producto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6><i class="fas fa-box me-2"></i>Información del Producto</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td id="detailNombre"></td>
                                </tr>
                                <tr>
                                    <td><strong>Modelo:</strong></td>
                                    <td id="detailModelo"></td>
                                </tr>
                                <tr>
                                    <td><strong>Talla:</strong></td>
                                    <td id="detailTalla"></td>
                                </tr>
                                <tr>
                                    <td><strong>Color:</strong></td>
                                    <td id="detailColor"></td>
                                </tr>
                                <tr>
                                    <td><strong>Material:</strong></td>
                                    <td id="detailMaterial"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="fas fa-layer-group me-2"></i>Stock y Fechas</h6>
                            <div class="text-center">
                                <div class="mb-3">
                                    <h3 id="detailStock" class="text-primary"></h3>
                                    <small class="text-muted">Unidades en stock</small>
                                </div>
                                <p><strong>Fecha de ingreso:</strong><br><span id="detailFecha"></span></p>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <h6><i class="fas fa-tags me-2"></i>Categorización</h6>
                            <p><strong>Categoría:</strong> <span id="detailCategoria"></span></p>
                            <p><strong>Subcategoría:</strong> <span id="detailSubcategoria"></span></p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="fas fa-truck me-2"></i>Proveedor</h6>
                            <p id="detailProveedor"></p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="fas fa-user me-2"></i>Usuario Responsable</h6>
                            <p id="detailUsuario"></p>
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
                        <i class="fas fa-box fa-3x text-danger mb-3"></i>
                        <p>¿Está seguro de que desea eliminar este producto?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Producto:</strong> <span id="productName"></span>
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
        let productToDelete = null;
        
        // Aplicar filtros
        function aplicarFiltros() {
            const nombre = document.getElementById('filtroNombre').value;
            const categoria = document.getElementById('filtroCategoria').value;
            const proveedor = document.getElementById('filtroProveedor').value;
            const stock = document.getElementById('filtroStock').value;
            
            let url = 'productos.php?';
            if (nombre) url += 'nombre=' + encodeURIComponent(nombre) + '&';
            if (categoria) url += 'categoria=' + encodeURIComponent(categoria) + '&';
            if (proveedor) url += 'proveedor=' + encodeURIComponent(proveedor) + '&';
            if (stock) url += 'stock=' + encodeURIComponent(stock) + '&';
            
            window.location.href = url.slice(0, -1);
        }
        
        // Filtrar productos con stock bajo
        function filtrarBajoStock() {
            window.location.href = 'productos.php?bajo_stock=1';
        }
        
        // Limpiar filtros
        function limpiarFiltros() {
            window.location.href = 'productos.php';
        }
        
        // Ver detalles
        function verDetalles(id, nombre, modelo, talla, color, stock, fecha, material, categoria, subcategoria, proveedor, usuario) {
            document.getElementById('detailNombre').textContent = nombre;
            document.getElementById('detailModelo').textContent = modelo;
            document.getElementById('detailTalla').textContent = talla;
            document.getElementById('detailColor').textContent = color;
            document.getElementById('detailMaterial').textContent = material;
            document.getElementById('detailStock').textContent = stock;
            document.getElementById('detailFecha').textContent = new Date(fecha).toLocaleDateString('es-ES');
            document.getElementById('detailCategoria').textContent = categoria || 'N/A';
            document.getElementById('detailSubcategoria').textContent = subcategoria || 'N/A';
            document.getElementById('detailProveedor').textContent = proveedor || 'Sin proveedor';
            document.getElementById('detailUsuario').textContent = usuario || 'Sin asignar';
            
            // Color del stock
            const stockElement = document.getElementById('detailStock');
            stockElement.className = 'text-' + (stock === 0 ? 'danger' : stock <= 10 ? 'warning' : 'success');
            
            new bootstrap.Modal(document.getElementById('detailsModal')).show();
        }
        
        // Editar producto
        function editarProducto(id, nombre, modelo, talla, color, stock, fecha, material, subcat, proveedor, usuario) {
            document.getElementById('editId').value = id;
            document.getElementById('editNombre').value = nombre;
            document.getElementById('editModelo').value = modelo;
            document.getElementById('editTalla').value = talla;
            document.getElementById('editColor').value = color;
            document.getElementById('editStock').value = stock;
            document.getElementById('editFechaIng').value = fecha;
            document.getElementById('editMaterial').value = material;
            
            // Cargar opciones dinámicamente
            cargarOpcionesEdicion(subcat, proveedor, usuario);
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        // Cargar opciones para el modal de edición
        function cargarOpcionesEdicion(selectedSubcat, selectedProveedor, selectedUsuario) {
            // Aquí podrías hacer llamadas AJAX para cargar las opciones actualizadas
            // Por simplicidad, usaremos los datos que ya tenemos
            const subcatSelect = document.getElementById('editSubcat');
            const proveedorSelect = document.getElementById('editProveedor');
            const usuarioSelect = document.getElementById('editUsuario');
            
            // Limpiar opciones actuales
            subcatSelect.innerHTML = '<option value="">Seleccione...</option>';
            proveedorSelect.innerHTML = '<option value="">Seleccione...</option>';
            usuarioSelect.innerHTML = '<option value="">Seleccione...</option>';
            
            // Cargar subcategorías (esto debería ser dinámico en una implementación real)
            <?php
            $subcats = $db->conn->query("SELECT sc.id_subcg, sc.nombre, c.nombre as categoria_nombre FROM Subcategoria sc JOIN Categoria c ON sc.id_categ = c.id_categ ORDER BY c.nombre, sc.nombre");
            while($subcat = $subcats->fetch_assoc()): 
            ?>
            subcatSelect.innerHTML += '<option value="<?= $subcat['id_subcg'] ?>"><?= addslashes($subcat['categoria_nombre']) ?> - <?= addslashes($subcat['nombre']) ?></option>';
            <?php endwhile; ?>
            
            // Cargar proveedores
            <?php
            $provs = $db->conn->query("SELECT id_nit, razon_social FROM Proveedores ORDER BY razon_social");
            while($prov = $provs->fetch_assoc()): 
            ?>
            proveedorSelect.innerHTML += '<option value="<?= $prov['id_nit'] ?>"><?= addslashes($prov['razon_social']) ?></option>';
            <?php endwhile; ?>
            
            // Cargar usuarios
            <?php
            $users = $db->conn->query("SELECT num_doc, nombres FROM Users ORDER BY nombres");
            while($user = $users->fetch_assoc()): 
            ?>
            usuarioSelect.innerHTML += '<option value="<?= $user['num_doc'] ?>"><?= addslashes($user['nombres']) ?></option>';
            <?php endwhile; ?>
            
            // Seleccionar valores actuales
            subcatSelect.value = selectedSubcat;
            proveedorSelect.value = selectedProveedor;
            usuarioSelect.value = selectedUsuario;
        }
        
        // Confirmar eliminación
        function confirmarEliminar(id, nombre) {
            productToDelete = id;
            document.getElementById('productName').textContent = nombre;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Procesar eliminación
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (productToDelete) {
                window.location.href = `productos.php?eliminar=${productToDelete}`;
            }
        });
        
        // Enter en filtros
        document.getElementById('filtroNombre').addEventListener('keypress', function(e) {
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
        });
    </script>
</body>
</html>