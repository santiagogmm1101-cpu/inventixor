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

// Obtener categorías para formularios
$categorias = [];
$resCat = $db->conn->query("SELECT id_categ, nombre FROM Categoria ORDER BY nombre");
while($cat = $resCat->fetch_assoc()) {
    $categorias[] = $cat;
}

// Eliminar subcategoría
if (isset($_GET['eliminar'])) {
    $id_subcategoria = intval($_GET['eliminar']);
    
    // Verificar si tiene productos asociados
    $prods = $db->conn->query("SELECT COUNT(*) FROM Productos WHERE id_subcg = $id_subcategoria");
    if ($prods->fetch_row()[0] > 0) {
        $errorMsg = "No se puede eliminar la subcategoría porque tiene productos asociados.";
    } else {
        $stmt = $db->conn->prepare("DELETE FROM Subcategoria WHERE id_subcg = ?");
        $stmt->bind_param('i', $id_subcategoria);
        $stmt->execute();
        $stmt->close();
        header('Location: subcategorias.php');
        exit;
    }
}

// Modificar subcategoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modificar_subcategoria'])) {
    $id = intval($_POST['id_subcg']);
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $id_categ = intval($_POST['id_categ']);
    $stmt = $db->conn->prepare("UPDATE Subcategoria SET nombre = ?, descripcion = ?, id_categ = ? WHERE id_subcg = ?");
    $stmt->bind_param('ssii', $nombre, $descripcion, $id_categ, $id);
    $stmt->execute();
    $stmt->close();
    header('Location: subcategorias.php');
    exit;
}

// Crear subcategoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_subcategoria'])) {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $id_categ = intval($_POST['id_categ']);
    $stmt = $db->conn->prepare("INSERT INTO Subcategoria (nombre, descripcion, id_categ) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $nombre, $descripcion, $id_categ);
    $stmt->execute();
    $stmt->close();
    header('Location: subcategorias.php');
    exit;
}

// Filtros
$filtro = '';
$categoria_filtro = '';

if (isset($_GET['filtro']) && $_GET['filtro'] !== '') {
    $filtro = $_GET['filtro'];
}
if (isset($_GET['categoria']) && $_GET['categoria'] !== '') {
    $categoria_filtro = intval($_GET['categoria']);
}

// Consulta con JOIN y filtros
$sql = "SELECT s.id_subcg, s.nombre, s.descripcion, s.id_categ, c.nombre AS categoria_nombre, 
               COUNT(p.id_prod) AS total_productos
        FROM Subcategoria s 
        LEFT JOIN Categoria c ON s.id_categ = c.id_categ 
        LEFT JOIN Productos p ON s.id_subcg = p.id_subcg";

$where_conditions = [];
$params = [];
$types = '';

if ($filtro) {
    $where_conditions[] = "s.nombre LIKE ?";
    $params[] = "%$filtro%";
    $types .= 's';
}

if ($categoria_filtro) {
    $where_conditions[] = "s.id_categ = ?";
    $params[] = $categoria_filtro;
    $types .= 'i';
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " GROUP BY s.id_subcg, s.nombre, s.descripcion, s.id_categ, c.nombre ORDER BY c.nombre, s.nombre";

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
    <title>Gestión de Subcategorías - Inventixor</title>
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
                <a href="subcategorias.php" class="menu-link active">
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
                        <h2><i class="fas fa-tag me-2"></i>Gestión de Subcategorías</h2>
                        <p class="mb-0">Administra las subcategorías organizadas por categorías</p>
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
                    <i class="fas fa-plus me-2"></i>Nueva Subcategoría
                </button>
            </div>
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="filtroInput" class="form-label">Buscar por nombre:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="filtroInput" class="form-control" placeholder="Filtrar por nombre" value="<?= htmlspecialchars($filtro) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="categoriaFiltro" class="form-label">Filtrar por categoría:</label>
                    <select id="categoriaFiltro" class="form-select">
                        <option value="">Todas las categorías</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?= $cat['id_categ'] ?>" <?= $categoria_filtro == $cat['id_categ'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
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
        
        <!-- Tabla de subcategorías -->
        <div class="table-card animate-fade-in">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                            <th><i class="fas fa-tag me-1"></i>Nombre</th>
                            <th><i class="fas fa-tags me-1"></i>Categoría</th>
                            <th><i class="fas fa-align-left me-1"></i>Descripción</th>
                            <th><i class="fas fa-box me-1"></i>Productos</th>
                            <th><i class="fas fa-cogs me-1"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge bg-primary"><?= $row['id_subcg'] ?></span></td>
                            <td><strong><?= htmlspecialchars($row['nombre']) ?></strong></td>
                            <td>
                                <span class="badge bg-success">
                                    <?= htmlspecialchars($row['categoria_nombre'] ?? 'Sin categoría') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['descripcion']) ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?= $row['total_productos'] ?> productos
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-action" 
                                            onclick="editarSubcategoria(<?= $row['id_subcg'] ?>, '<?= addslashes($row['nombre']) ?>', '<?= addslashes($row['descripcion']) ?>', <?= $row['id_categ'] ?>)"
                                            title="Editar Subcategoría">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <?php if ($_SESSION['rol'] !== 'auxiliar'): ?>
                                    <button type="button" class="btn btn-outline-danger btn-action"
                                            onclick="confirmarEliminar(<?= $row['id_subcg'] ?>, '<?= addslashes($row['nombre']) ?>')"
                                            title="Eliminar Subcategoría">
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

    <!-- Modal para crear subcategoría -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Nueva Subcategoría
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombreSub" class="form-label">
                                <i class="fas fa-tag me-1"></i>Nombre de la Subcategoría
                            </label>
                            <input type="text" name="nombre" id="nombreSub" class="form-control" 
                                   placeholder="Ej: Zapatos de Running" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label for="categoriaSub" class="form-label">
                                <i class="fas fa-tags me-1"></i>Categoría Padre
                            </label>
                            <select name="id_categ" id="categoriaSub" class="form-select" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach($categorias as $cat): ?>
                                    <option value="<?= $cat['id_categ'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="descripcionSub" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Descripción
                            </label>
                            <textarea name="descripcion" id="descripcionSub" class="form-control" rows="3"
                                    placeholder="Descripción detallada de la subcategoría" required maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_subcategoria" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar subcategoría -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #ffc107, #ff8f00); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Subcategoría
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="id_subcg" id="editId">
                        <div class="mb-3">
                            <label for="editNombre" class="form-label">
                                <i class="fas fa-tag me-1"></i>Nombre de la Subcategoría
                            </label>
                            <input type="text" name="nombre" id="editNombre" class="form-control" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label for="editCategoria" class="form-label">
                                <i class="fas fa-tags me-1"></i>Categoría Padre
                            </label>
                            <select name="id_categ" id="editCategoria" class="form-select" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach($categorias as $cat): ?>
                                    <option value="<?= $cat['id_categ'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editDescripcion" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Descripción
                            </label>
                            <textarea name="descripcion" id="editDescripcion" class="form-control" rows="3" required maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="modificar_subcategoria" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Actualizar
                        </button>
                    </div>
                </form>
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
                        <p>¿Está seguro de que desea eliminar la subcategoría?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Subcategoría:</strong> <span id="subcategoryName"></span>
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
        let subcategoryToDelete = null;
        
        // Función para aplicar filtros
        function aplicarFiltros() {
            const filtro = document.getElementById('filtroInput').value;
            const categoria = document.getElementById('categoriaFiltro').value;
            let url = 'subcategorias.php?';
            
            if (filtro) url += 'filtro=' + encodeURIComponent(filtro) + '&';
            if (categoria) url += 'categoria=' + encodeURIComponent(categoria) + '&';
            
            window.location.href = url.slice(0, -1); // Remover último &
        }
        
        // Función para limpiar filtros
        function limpiarFiltros() {
            window.location.href = 'subcategorias.php';
        }
        
        // Función para editar subcategoría
        function editarSubcategoria(id, nombre, descripcion, id_categ) {
            document.getElementById('editId').value = id;
            document.getElementById('editNombre').value = nombre;
            document.getElementById('editDescripcion').value = descripcion;
            document.getElementById('editCategoria').value = id_categ;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        // Función para confirmar eliminación
        function confirmarEliminar(id, nombre) {
            subcategoryToDelete = id;
            document.getElementById('subcategoryName').textContent = nombre;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Procesar eliminación
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (subcategoryToDelete) {
                window.location.href = `subcategorias.php?eliminar=${subcategoryToDelete}`;
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