<?php
// categorias.php
require_once 'app/helpers/Database.php';
$db = new Database();

// Eliminar categoría
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = $db->conn->prepare("DELETE FROM Categoria WHERE id_categ = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    header('Location: categorias.php');
    exit;
}

// Modificar categoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modificar_categoria'])) {
    $id = intval($_POST['id_categ']);
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $stmt = $db->conn->prepare("UPDATE Categoria SET nombre = ?, descripcion = ? WHERE id_categ = ?");
    $stmt->bind_param('ssi', $nombre, $descripcion, $id);
    $stmt->execute();
    $stmt->close();
    header('Location: categorias.php');
    exit;
}

// Procesar formulario de creación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_categoria'])) {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $stmt = $db->conn->prepare("INSERT INTO Categoria (nombre, descripcion) VALUES (?, ?)");
    $stmt->bind_param('ss', $nombre, $descripcion);
    $stmt->execute();
    $stmt->close();
    header('Location: categorias.php');
    exit;
}

// Filtrar categorías
$filtro = '';
if (isset($_GET['filtro']) && $_GET['filtro'] !== '') {
    $filtro = $_GET['filtro'];
    $sql = "SELECT id_categ, nombre, descripcion FROM Categoria WHERE nombre LIKE ?";
    $stmt = $db->conn->prepare($sql);
    $like = "%$filtro%";
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $sql = "SELECT id_categ, nombre, descripcion FROM Categoria";
    $result = $db->conn->query($sql);
}

// Para mostrar el formulario de modificar
$editCategoria = null;
if (isset($_GET['modificar'])) {
    $id = intval($_GET['modificar']);
    $sql = "SELECT id_categ, nombre, descripcion FROM Categoria WHERE id_categ = ?";
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $editCategoria = $res->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Categorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(120deg, #e3e6e8 0%, #cfd8dc 100%);
        }
        .container {
            background: #f7f9fa;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(60,72,88,0.10);
            padding: 40px 32px;
            max-width: 900px;
            position: relative;
            z-index: 1;
        }
        h2 {
            color: #263238;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 32px;
        }
        .btn {
            border-radius: 8px;
            font-weight: 500;
            box-shadow: none;
        }
        .btn-success {
            background: #388e3c;
            border: none;
        }
        .btn-primary {
            background: #1976d2;
            border: none;
        }
        .btn-warning {
            background: #ffa000;
            border: none;
            color: #fff;
        }
        .btn-danger {
            background: #d32f2f;
            border: none;
        }
        .btn-secondary {
            background: #455a64;
            border: none;
        }
        .table {
            background: #eceff1;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 16px;
        }
        thead th {
            background: #b0bec5;
            color: #263238;
            font-size: 1.05rem;
            font-weight: 600;
        }
        tbody tr {
            transition: background 0.2s;
        }
        tbody tr:hover {
            background: #cfd8dc;
        }
        .form-control {
            border-radius: 8px;
            box-shadow: none;
        }
        #formCrear, .mb-3 > form {
            background: #eceff1;
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(25,118,210,0.08);
        }
        .mb-3.d-flex {
            margin-bottom: 24px !important;
        }
        @media (max-width: 900px) {
            .container {
                padding: 24px 8px;
            }
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-end mb-3">
        <a href="dashboard.php" class="btn btn-secondary">Menú principal</a>
    </div>
    <h2 class="mb-4 text-center">Gestión de Categorías</h2>
    <!-- Formulario de creación -->
    <?php if($editCategoria): ?>
    <div class="mb-3">
        <form method="POST" class="d-flex gap-2 align-items-end">
            <input type="hidden" name="id_categ" value="<?= $editCategoria['id_categ'] ?>">
            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($editCategoria['nombre']) ?>" required style="max-width:200px;">
            <input type="text" name="descripcion" class="form-control" value="<?= htmlspecialchars($editCategoria['descripcion']) ?>" required style="max-width:300px;">
            <button type="submit" name="modificar_categoria" class="btn btn-warning">Actualizar</button>
            <a href="categorias.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
    <?php endif; ?>
    <div class="mb-3 d-flex gap-2">
        <input type="text" id="filtroInput" class="form-control w-auto" placeholder="Filtrar por nombre" style="max-width:200px;" value="<?= htmlspecialchars($filtro) ?>">
        <button class="btn btn-primary" onclick="window.location.href='categorias.php?filtro='+document.getElementById('filtroInput').value">Filtrar</button>
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nombre']) ?></td>
                <td><?= htmlspecialchars($row['descripcion']) ?></td>
                <td>
                    <a href="categorias.php?modificar=<?= $row['id_categ'] ?>" class="btn btn-warning btn-sm">Modificar</a>
                    <a href="categorias.php?eliminar=<?= $row['id_categ'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que deseas eliminar esta categoría?')">Eliminar</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <div class="mt-4 text-center">
        <button class="btn btn-success mb-3" id="btnMostrarFormCat">Crear nueva categoría</button>
        <form method="POST" id="formCrearCat" class="p-4 rounded shadow-sm bg-white mx-auto" style="max-width: 600px; display:none;">
            <h4 class="mb-3 text-center" style="color:#1976d2;font-weight:600;">Crear nueva categoría</h4>
            <div class="mb-3">
                <label for="nombreCat" class="form-label">Nombre</label>
                <input type="text" name="nombre" id="nombreCat" class="form-control" placeholder="Nombre de la categoría" required maxlength="100">
            </div>
            <div class="mb-3">
                <label for="descripcionCat" class="form-label">Descripción</label>
                <input type="text" name="descripcion" id="descripcionCat" class="form-control" placeholder="Descripción" required maxlength="255">
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <button type="submit" name="crear_categoria" class="btn btn-success">Guardar</button>
                <button type="button" class="btn btn-secondary" id="btnCancelarFormCat">Cancelar</button>
            </div>
        </form>
    </div>
    <script>
        document.getElementById('btnMostrarFormCat').onclick = function() {
            document.getElementById('formCrearCat').style.display = 'block';
            this.style.display = 'none';
        };
        document.getElementById('btnCancelarFormCat').onclick = function() {
            document.getElementById('formCrearCat').reset();
            document.getElementById('formCrearCat').style.display = 'none';
            document.getElementById('btnMostrarFormCat').style.display = 'inline-block';
        };
    </script>
</div>
</body>
</html>
