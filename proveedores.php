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

// Eliminar proveedor
if (isset($_GET['eliminar'])) {
    $id_nit = intval($_GET['eliminar']);
    
    // Verificar si tiene productos o reportes asociados
    $productos = $db->conn->query("SELECT COUNT(*) FROM Productos WHERE id_nit = $id_nit");
    $reportes = $db->conn->query("SELECT COUNT(*) FROM Reportes WHERE id_nit = $id_nit");
    
    $prodCount = $productos->fetch_row()[0];
    $repCount = $reportes->fetch_row()[0];
    
    if ($prodCount > 0 || $repCount > 0) {
        $entidades = [];
        if ($prodCount > 0) $entidades[] = "productos ($prodCount)";
        if ($repCount > 0) $entidades[] = "reportes ($repCount)";
        $errorMsg = "No se puede eliminar el proveedor porque tiene " . implode(' y ', $entidades) . " asociados.";
    } else {
        $stmt = $db->conn->prepare("DELETE FROM Proveedores WHERE id_nit = ?");
        $stmt->bind_param('i', $id_nit);
        $stmt->execute();
        $stmt->close();
        header('Location: proveedores.php');
        exit;
    }
}

// Modificar proveedor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modificar_proveedor'])) {
    $id_nit = intval($_POST['id_nit']);
    $razon_social = $_POST['razon_social'];
    $contacto = $_POST['contacto'];
    $direccion = $_POST['direccion'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $estado = $_POST['estado'];
    $detalles = $_POST['detalles'];
    
    $stmt = $db->conn->prepare("UPDATE Proveedores SET razon_social = ?, contacto = ?, direccion = ?, correo = ?, telefono = ?, estado = ?, detalles = ? WHERE id_nit = ?");
    $stmt->bind_param('sssssssi', $razon_social, $contacto, $direccion, $correo, $telefono, $estado, $detalles, $id_nit);
    $stmt->execute();
    $stmt->close();
    header('Location: proveedores.php');
    exit;
}

// Crear proveedor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_proveedor'])) {
    $razon_social = $_POST['razon_social'];
    $contacto = $_POST['contacto'];
    $direccion = $_POST['direccion'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $estado = $_POST['estado'];
    $detalles = $_POST['detalles'];
    
    $stmt = $db->conn->prepare("INSERT INTO Proveedores (razon_social, contacto, direccion, correo, telefono, estado, detalles) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssssss', $razon_social, $contacto, $direccion, $correo, $telefono, $estado, $detalles);
    $stmt->execute();
    $stmt->close();
    header('Location: proveedores.php');
    exit;
}

// Filtros
$filtro = '';
$estado_filtro = '';

if (isset($_GET['filtro']) && $_GET['filtro'] !== '') {
    $filtro = $_GET['filtro'];
}
if (isset($_GET['estado']) && $_GET['estado'] !== '') {
    $estado_filtro = $_GET['estado'];
}

// Consulta con JOIN para obtener estadísticas
$sql = "SELECT p.id_nit, p.razon_social, p.contacto, p.direccion, p.correo, p.telefono, p.estado, p.detalles,
               COUNT(DISTINCT pr.id_prod) AS total_productos,
               COUNT(DISTINCT r.id_repor) AS total_reportes
        FROM Proveedores p 
        LEFT JOIN Productos pr ON p.id_nit = pr.id_nit 
        LEFT JOIN Reportes r ON p.id_nit = r.id_nit";

$where_conditions = [];
$params = [];
$types = '';

if ($filtro) {
    $where_conditions[] = "(p.razon_social LIKE ? OR p.contacto LIKE ? OR p.correo LIKE ?)";
    $params[] = "%$filtro%";
    $params[] = "%$filtro%";
    $params[] = "%$filtro%";
    $types .= 'sss';
}

if ($estado_filtro) {
    $where_conditions[] = "p.estado = ?";
    $params[] = $estado_filtro;
    $types .= 's';
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " GROUP BY p.id_nit ORDER BY p.razon_social";

if (!empty($params)) {
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $db->conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores - Inventixor</title>
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
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .filter-card {
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
        
        .provider-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .provider-card:hover {
            transform: translateY(-2px);
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
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
                <a href="proveedores.php" class="menu-link active">
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
                        <h2><i class="fas fa-truck me-2"></i>Gestión de Proveedores</h2>
                        <p class="mb-0">Administra los proveedores y sus productos asociados</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-light text-dark">
                            Rol: <?= htmlspecialchars($_SESSION['rol']??'') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filtros y búsqueda -->
        <div class="filter-card animate-fade-in">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-filter me-2"></i>Filtros y Búsqueda</h5>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="fas fa-plus me-2"></i>Nuevo Proveedor
                </button>
            </div>
            <div class="row align-items-end">
                <div class="col-md-5">
                    <label for="filtroInput" class="form-label">Buscar por razón social, contacto o correo:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="filtroInput" class="form-control" placeholder="Buscar proveedor..." value="<?= htmlspecialchars($filtro) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="estadoFiltro" class="form-label">Filtrar por estado:</label>
                    <select id="estadoFiltro" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="Activo" <?= $estado_filtro === 'Activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="Inactivo" <?= $estado_filtro === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        <option value="Pendiente" <?= $estado_filtro === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" onclick="aplicarFiltros()">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100" onclick="limpiarFiltros()">
                        <i class="fas fa-times me-1"></i>Limpiar
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Tabla de proveedores -->
        <div class="table-card animate-fade-in">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>NIT</th>
                            <th><i class="fas fa-building me-1"></i>Razón Social</th>
                            <th><i class="fas fa-user me-1"></i>Contacto</th>
                            <th><i class="fas fa-envelope me-1"></i>Correo</th>
                            <th><i class="fas fa-phone me-1"></i>Teléfono</th>
                            <th><i class="fas fa-toggle-on me-1"></i>Estado</th>
                            <th><i class="fas fa-chart-line me-1"></i>Estadísticas</th>
                            <th><i class="fas fa-cogs me-1"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge bg-primary"><?= $row['id_nit'] ?></span></td>
                            <td><strong><?= htmlspecialchars($row['razon_social']) ?></strong></td>
                            <td><?= htmlspecialchars($row['contacto']) ?></td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($row['correo']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($row['correo']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($row['telefono']) ?></td>
                            <td>
                                <?php
                                $badgeClass = '';
                                switch($row['estado']) {
                                    case 'Activo': $badgeClass = 'bg-success'; break;
                                    case 'Inactivo': $badgeClass = 'bg-secondary'; break;
                                    case 'Pendiente': $badgeClass = 'bg-warning'; break;
                                    default: $badgeClass = 'bg-info';
                                }
                                ?>
                                <span class="badge <?= $badgeClass ?> status-badge">
                                    <?= htmlspecialchars($row['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <small class="d-block">
                                    <i class="fas fa-box me-1"></i>
                                    <span class="badge bg-info"><?= $row['total_productos'] ?></span> productos
                                </small>
                                <small class="d-block">
                                    <i class="fas fa-chart-bar me-1"></i>
                                    <span class="badge bg-secondary"><?= $row['total_reportes'] ?></span> reportes
                                </small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-action" 
                                            onclick="verDetalles(<?= $row['id_nit'] ?>, '<?= addslashes($row['razon_social']) ?>', '<?= addslashes($row['contacto']) ?>', '<?= addslashes($row['direccion']) ?>', '<?= addslashes($row['correo']) ?>', '<?= addslashes($row['telefono']) ?>', '<?= addslashes($row['estado']) ?>', '<?= addslashes($row['detalles']) ?>')"
                                            title="Ver Detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <button type="button" class="btn btn-outline-warning btn-action" 
                                            onclick="editarProveedor(<?= $row['id_nit'] ?>, '<?= addslashes($row['razon_social']) ?>', '<?= addslashes($row['contacto']) ?>', '<?= addslashes($row['direccion']) ?>', '<?= addslashes($row['correo']) ?>', '<?= addslashes($row['telefono']) ?>', '<?= addslashes($row['estado']) ?>', '<?= addslashes($row['detalles']) ?>')"
                                            title="Editar Proveedor">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <?php if ($_SESSION['rol'] !== 'auxiliar'): ?>
                                    <button type="button" class="btn btn-outline-danger btn-action"
                                            onclick="confirmarEliminar(<?= $row['id_nit'] ?>, '<?= addslashes($row['razon_social']) ?>')"
                                            title="Eliminar Proveedor">
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

    <!-- Modal para crear proveedor -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Nuevo Proveedor
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="razonSocial" class="form-label">
                                        <i class="fas fa-building me-1"></i>Razón Social
                                    </label>
                                    <input type="text" name="razon_social" id="razonSocial" class="form-control" 
                                           placeholder="Ej: Empresa ABC S.A.S." required maxlength="255">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contacto" class="form-label">
                                        <i class="fas fa-user me-1"></i>Persona de Contacto
                                    </label>
                                    <input type="text" name="contacto" id="contacto" class="form-control" 
                                           placeholder="Nombre del contacto" required maxlength="100">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="direccion" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>Dirección
                                    </label>
                                    <input type="text" name="direccion" id="direccion" class="form-control" 
                                           placeholder="Dirección completa" required maxlength="200">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="estado" class="form-label">
                                        <i class="fas fa-toggle-on me-1"></i>Estado
                                    </label>
                                    <select name="estado" id="estado" class="form-select" required>
                                        <option value="Activo">Activo</option>
                                        <option value="Inactivo">Inactivo</option>
                                        <option value="Pendiente">Pendiente</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="correo" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Correo Electrónico
                                    </label>
                                    <input type="email" name="correo" id="correo" class="form-control" 
                                           placeholder="correo@ejemplo.com" required maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">
                                        <i class="fas fa-phone me-1"></i>Teléfono
                                    </label>
                                    <input type="tel" name="telefono" id="telefono" class="form-control" 
                                           placeholder="+57 300 123 4567" required maxlength="20">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="detalles" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Detalles Adicionales
                            </label>
                            <textarea name="detalles" id="detalles" class="form-control" rows="3"
                                    placeholder="Información adicional sobre el proveedor" maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_proveedor" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar proveedor -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #ffc107, #ff8f00); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Proveedor
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="id_nit" id="editId">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editRazonSocial" class="form-label">
                                        <i class="fas fa-building me-1"></i>Razón Social
                                    </label>
                                    <input type="text" name="razon_social" id="editRazonSocial" class="form-control" required maxlength="255">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editContacto" class="form-label">
                                        <i class="fas fa-user me-1"></i>Persona de Contacto
                                    </label>
                                    <input type="text" name="contacto" id="editContacto" class="form-control" required maxlength="100">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="editDireccion" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>Dirección
                                    </label>
                                    <input type="text" name="direccion" id="editDireccion" class="form-control" required maxlength="200">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="editEstado" class="form-label">
                                        <i class="fas fa-toggle-on me-1"></i>Estado
                                    </label>
                                    <select name="estado" id="editEstado" class="form-select" required>
                                        <option value="Activo">Activo</option>
                                        <option value="Inactivo">Inactivo</option>
                                        <option value="Pendiente">Pendiente</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editCorreo" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Correo Electrónico
                                    </label>
                                    <input type="email" name="correo" id="editCorreo" class="form-control" required maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editTelefono" class="form-label">
                                        <i class="fas fa-phone me-1"></i>Teléfono
                                    </label>
                                    <input type="tel" name="telefono" id="editTelefono" class="form-control" required maxlength="20">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editDetalles" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Detalles Adicionales
                            </label>
                            <textarea name="detalles" id="editDetalles" class="form-control" rows="3" maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="modificar_proveedor" class="btn btn-warning">
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
                        <i class="fas fa-eye me-2"></i>Detalles del Proveedor
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-building me-2"></i>Razón Social</h6>
                            <p id="detailRazonSocial" class="fw-bold"></p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-user me-2"></i>Contacto</h6>
                            <p id="detailContacto"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <h6><i class="fas fa-map-marker-alt me-2"></i>Dirección</h6>
                            <p id="detailDireccion"></p>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="fas fa-toggle-on me-2"></i>Estado</h6>
                            <p><span id="detailEstado" class="badge"></span></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-envelope me-2"></i>Correo</h6>
                            <p id="detailCorreo"></p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-phone me-2"></i>Teléfono</h6>
                            <p id="detailTelefono"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <h6><i class="fas fa-align-left me-2"></i>Detalles</h6>
                            <p id="detailDetalles" class="text-muted"></p>
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
                        <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                        <p>¿Está seguro de que desea eliminar el proveedor?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Proveedor:</strong> <span id="providerName"></span>
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

    <?php if(isset($errorMsg)): ?>
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Atención:</strong> <?= htmlspecialchars($errorMsg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let providerToDelete = null;
        
        // Función para aplicar filtros
        function aplicarFiltros() {
            const filtro = document.getElementById('filtroInput').value;
            const estado = document.getElementById('estadoFiltro').value;
            let url = 'proveedores.php?';
            
            if (filtro) url += 'filtro=' + encodeURIComponent(filtro) + '&';
            if (estado) url += 'estado=' + encodeURIComponent(estado) + '&';
            
            window.location.href = url.slice(0, -1);
        }
        
        // Función para limpiar filtros
        function limpiarFiltros() {
            window.location.href = 'proveedores.php';
        }
        
        // Función para ver detalles
        function verDetalles(id, razonSocial, contacto, direccion, correo, telefono, estado, detalles) {
            document.getElementById('detailRazonSocial').textContent = razonSocial;
            document.getElementById('detailContacto').textContent = contacto;
            document.getElementById('detailDireccion').textContent = direccion;
            document.getElementById('detailCorreo').textContent = correo;
            document.getElementById('detailTelefono').textContent = telefono;
            document.getElementById('detailDetalles').textContent = detalles || 'Sin detalles adicionales';
            
            const estadoBadge = document.getElementById('detailEstado');
            estadoBadge.textContent = estado;
            estadoBadge.className = 'badge ' + (estado === 'Activo' ? 'bg-success' : estado === 'Inactivo' ? 'bg-secondary' : 'bg-warning');
            
            new bootstrap.Modal(document.getElementById('detailsModal')).show();
        }
        
        // Función para editar proveedor
        function editarProveedor(id, razonSocial, contacto, direccion, correo, telefono, estado, detalles) {
            document.getElementById('editId').value = id;
            document.getElementById('editRazonSocial').value = razonSocial;
            document.getElementById('editContacto').value = contacto;
            document.getElementById('editDireccion').value = direccion;
            document.getElementById('editCorreo').value = correo;
            document.getElementById('editTelefono').value = telefono;
            document.getElementById('editEstado').value = estado;
            document.getElementById('editDetalles').value = detalles;
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        // Función para confirmar eliminación
        function confirmarEliminar(id, razonSocial) {
            providerToDelete = id;
            document.getElementById('providerName').textContent = razonSocial;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Procesar eliminación
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (providerToDelete) {
                window.location.href = `proveedores.php?eliminar=${providerToDelete}`;
            }
        });
        
        // Enter en filtros
        document.getElementById('filtroInput').addEventListener('keypress', function(e) {
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