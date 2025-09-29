<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
require_once 'app/helpers/Database.php';
$db = new Database();

// Obtener subcategorías, proveedores y usuarios para el formulario
$subcategorias = $db->conn->query("SELECT id_subcg, nombre FROM Subcategoria");
$proveedores = $db->conn->query("SELECT id_nit, razon_social FROM Proveedores");
$usuarios = $db->conn->query("SELECT num_doc, nombres FROM Users");

// Procesar formulario de creación de producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_producto'])) {
    $nombre = $_POST['nombre'];
    $modelo = $_POST['modelo'];
    $talla = $_POST['talla'];
    $color = $_POST['color'];
    $stock = $_POST['stock'];
    $fecha_ing = $_POST['fecha_ing'];
    $material = $_POST['material'];
    $id_subcg = intval($_POST['id_subcg']);
    $id_nit = intval($_POST['id_nit']);
    $num_doc = intval($_POST['num_doc']);
    $stmt = $db->conn->prepare("INSERT INTO Productos (nombre, modelo, talla, color, stock, fecha_ing, material, id_subcg, id_nit, num_doc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssssssii', $nombre, $modelo, $talla, $color, $stock, $fecha_ing, $material, $id_subcg, $id_nit, $num_doc);
    $stmt->execute();
    $stmt->close();
    header('Location: productos.php');
    exit;
}

// Consulta para mostrar productos con vínculos
$sql = "SELECT p.id_prod, p.nombre, p.modelo, p.talla, p.color, p.stock, p.fecha_ing, p.material, s.nombre AS subcategoria, pr.razon_social AS proveedor, u.nombres AS usuario FROM Productos p LEFT JOIN Subcategoria s ON p.id_subcg = s.id_subcg LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit LEFT JOIN Users u ON p.num_doc = u.num_doc";
$result = $db->conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; background: linear-gradient(120deg, #e3e6e8 0%, #cfd8dc 100%); }
        .container { background: #f7f9fa; border-radius: 14px; box-shadow: 0 4px 18px rgba(60,72,88,0.10); padding: 40px 32px; max-width: 1100px; position: relative; z-index: 1; }
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
        @media (max-width: 1100px) { .container { padding: 24px 8px; } }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-end mb-3">
        <a href="dashboard.php" class="btn btn-secondary">Menú principal</a>
    </div>
    <h2 class="mb-4 text-center">Gestión de Productos</h2>
    <div class="mb-3 d-flex gap-2">
        <input type="text" id="filtroInput" class="form-control w-auto" placeholder="Filtrar por nombre" style="max-width:200px;">
        <button class="btn btn-primary">Filtrar</button>
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Modelo</th>
                <th>Talla</th>
                <th>Color</th>
                <th>Stock</th>
                <th>Fecha Ingreso</th>
                <th>Material</th>
                <th>Subcategoría</th>
                <th>Proveedor</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nombre']) ?></td>
                <td><?= htmlspecialchars($row['modelo']) ?></td>
                <td><?= htmlspecialchars($row['talla']) ?></td>
                <td><?= htmlspecialchars($row['color']) ?></td>
                <td><?= htmlspecialchars($row['stock']) ?></td>
                <td><?= htmlspecialchars($row['fecha_ing']) ?></td>
                <td><?= htmlspecialchars($row['material']) ?></td>
                <td><?= htmlspecialchars($row['subcategoria']) ?></td>
                <td><?= htmlspecialchars($row['proveedor']) ?></td>
                <td><?= htmlspecialchars($row['usuario']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <div class="mt-4 text-center">
        <button class="btn btn-success" onclick="document.getElementById('crearProdContainer').style.display='block'">Crear nuevo producto</button>
    </div>
<!-- Modal Bootstrap fuera del div principal -->
<div class="modal fade" id="modalCrearProd" tabindex="-1" aria-labelledby="modalCrearProdLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCrearProdLabel">Crear producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formCrearProd">
                    <div class="mb-3">
                        <input type="text" name="nombre" class="form-control" placeholder="Nombre" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="modelo" class="form-control" placeholder="Modelo" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="talla" class="form-control" placeholder="Talla" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="color" class="form-control" placeholder="Color" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="stock" class="form-control" placeholder="Stock" required>
                    </div>
                    <div class="mb-3">
                        <input type="date" name="fecha_ing" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="material" class="form-control" placeholder="Material" required>
                    </div>
                    <div class="mb-3">
                        <select name="id_subcg" class="form-control" required>
                            <option value="">Subcategoría</option>
                            <?php $subs = $db->conn->query("SELECT id_subcg, nombre FROM Subcategoria"); while($sub = $subs->fetch_assoc()): ?>
                                <option value="<?= $sub['id_subcg'] ?>"><?= htmlspecialchars($sub['nombre']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <select name="id_nit" class="form-control" required>
                            <option value="">Proveedor</option>
                            <?php $provs = $db->conn->query("SELECT id_nit, razon_social FROM Proveedores"); while($prov = $provs->fetch_assoc()): ?>
                                <option value="<?= $prov['id_nit'] ?>"><?= htmlspecialchars($prov['razon_social']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <select name="num_doc" class="form-control" required>
                            <option value="">Usuario</option>
                            <?php $usrs = $db->conn->query("SELECT num_doc, nombres FROM Users"); while($usr = $usrs->fetch_assoc()): ?>
                                <option value="<?= $usr['num_doc'] ?>"><?= htmlspecialchars($usr['nombres']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="submit" name="crear_producto" class="btn btn-success">Guardar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="document.getElementById('formCrearProd').reset();">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>
