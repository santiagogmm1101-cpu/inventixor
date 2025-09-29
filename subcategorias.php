<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
require_once 'app/helpers/Database.php';
$db = new Database();

// Obtener categorías en array para usar en todos los formularios
$categorias = [];
$resCat = $db->conn->query("SELECT id_categ, nombre FROM Categoria");
while($cat = $resCat->fetch_assoc()) {
    $categorias[] = $cat;
}

// Eliminar subcategoría
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = $db->conn->prepare("DELETE FROM Subcategoria WHERE id_subcg = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    header('Location: subcategorias.php');
    exit;
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

// Procesar formulario de creación de subcategoría
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

// Consulta para mostrar subcategorías con nombre de categoría
$sql = "SELECT s.id_subcg, s.nombre, s.descripcion, c.nombre AS categoria FROM Subcategoria s LEFT JOIN Categoria c ON s.id_categ = c.id_categ";
$result = $db->conn->query($sql);
// Para mostrar el formulario de modificar
$editSubcategoria = null;
if (isset($_GET['modificar'])) {
    $id = intval($_GET['modificar']);
    $sql = "SELECT id_subcg, nombre, descripcion, id_categ FROM Subcategoria WHERE id_subcg = ?";
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $editSubcategoria = $res->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subcategorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; background: linear-gradient(120deg, #e3e6e8 0%, #cfd8dc 100%); }
        .container { background: #f7f9fa; border-radius: 14px; box-shadow: 0 4px 18px rgba(60,72,88,0.10); padding: 40px 32px; max-width: 900px; position: relative; z-index: 1; }
        h2 { color: #263238; font-weight: 700; letter-spacing: 1px; margin-bottom: 32px; }
        .btn { border-radius: 8px; font-weight: 500; box-shadow: none; }
        .btn-success { background: #388e3c; border: none; }
        .btn-primary { background: #1976d2; border: none; }
        .btn-warning { background: #ffa000; border: none; color: #fff; }
        .btn-danger { background: #d32f2f; border: none; }
        .btn-secondary { background: #455a64; border: none; }
        .table { background: #eceff1; border-radius: 10px; overflow: hidden; margin-top: 16px; }
        thead th { background: #b0bec5; color: #263238; font-size: 1.05rem; font-weight: 600; }
        tbody tr { transition: background 0.2s; }
        tbody tr:hover { background: #cfd8dc; }
        .form-control { border-radius: 8px; box-shadow: none; }
        #formCrear, .mb-3 > form { background: #eceff1; border-radius: 10px; padding: 16px; box-shadow: 0 2px 8px rgba(25,118,210,0.08); }
        .mb-3.d-flex { margin-bottom: 24px !important; }
        @media (max-width: 900px) { .container { padding: 24px 8px; } }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-end mb-3">
        <a href="dashboard.php" class="btn btn-secondary">Menú principal</a>
    </div>
    <h2 class="mb-4 text-center">Gestión de Subcategorías</h2>
    <div class="mb-3 d-flex gap-2">
        <input type="text" id="filtroInput" class="form-control w-auto" placeholder="Filtrar por nombre" style="max-width:200px;">
        <button class="btn btn-primary">Filtrar</button>
    </div>
    <?php if ($editSubcategoria): ?>
    <div class="mb-3">
        <form method="POST" class="d-flex gap-2 align-items-end justify-content-center">
            <input type="hidden" name="id_subcg" value="<?= $editSubcategoria['id_subcg'] ?>">
            <input type="text" name="nombre" class="form-control" placeholder="Nombre" required style="max-width:150px;" value="<?= htmlspecialchars($editSubcategoria['nombre']) ?>">
            <input type="text" name="descripcion" class="form-control" placeholder="Descripción" required style="max-width:200px;" value="<?= htmlspecialchars($editSubcategoria['descripcion']) ?>">
            <select name="id_categ" class="form-control" required style="max-width:200px;">
                <option value="">Seleccione categoría</option>
                <?php foreach($categorias as $cat): ?>
                    <option value="<?= $cat['id_categ'] ?>" <?= isset($editSubcategoria) && $cat['id_categ'] == $editSubcategoria['id_categ'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="modificar_subcategoria" class="btn btn-warning">Guardar cambios</button>
            <a href="subcategorias.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
    <?php endif; ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Categoría vinculada</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nombre']) ?></td>
                <td><?= htmlspecialchars($row['descripcion']) ?></td>
                <td><?= htmlspecialchars($row['categoria']) ?></td>
                <td>
                    <a href="subcategorias.php?modificar=<?= $row['id_subcg'] ?>" class="btn btn-warning btn-sm">Editar</a>
                    <a href="subcategorias.php?eliminar=<?= $row['id_subcg'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que deseas eliminar esta subcategoría?')">Eliminar</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <div class="mb-4 text-center">
        <button class="btn btn-success mb-3" id="btnMostrarForm">Crear subcategoría</button>
        <form method="POST" id="formCrearSubcat" class="p-4 rounded shadow-sm bg-white mx-auto" style="max-width: 600px; display:none;">
            <h4 class="mb-3 text-center" style="color:#1976d2;font-weight:600;">Crear nueva subcategoría</h4>
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Nombre de la subcategoría" required maxlength="100">
            </div>
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <input type="text" name="descripcion" id="descripcion" class="form-control" placeholder="Descripción" required maxlength="255">
            </div>
            <div class="mb-3">
                <label for="id_categ" class="form-label">Categoría vinculada</label>
                <select name="id_categ" id="id_categ" class="form-control" required>
                    <option value="">Seleccione categoría</option>
                    <?php foreach($categorias as $cat): ?>
                        <option value="<?= $cat['id_categ'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <button type="submit" name="crear_subcategoria" class="btn btn-success">Guardar</button>
                <button type="button" class="btn btn-secondary" id="btnCancelarForm">Cancelar</button>
            </div>
        </form>
    </div>
    <script>
        document.getElementById('btnMostrarForm').onclick = function() {
            document.getElementById('formCrearSubcat').style.display = 'block';
            this.style.display = 'none';
        };
        document.getElementById('btnCancelarForm').onclick = function() {
            document.getElementById('formCrearSubcat').reset();
            document.getElementById('formCrearSubcat').style.display = 'none';
            document.getElementById('btnMostrarForm').style.display = 'inline-block';
        };
    </script>
</body>
</html>
