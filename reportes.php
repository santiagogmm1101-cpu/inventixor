<?php
require_once 'app/helpers/Database.php';
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$db = new Database();

// Obtener productos para el formulario de reporte
$productos = $db->conn->query("SELECT id_prod, nombre FROM Productos");

// Procesar formulario de creación de reporte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_reporte'])) {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $fecha_hora = $_POST['fecha_hora'];
    $id_prod = intval($_POST['id_prod']);
    $stmt = $db->conn->prepare("INSERT INTO Reportes (nombre, descripcion, fecha_hora, id_prod) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('sssi', $nombre, $descripcion, $fecha_hora, $id_prod);
    $stmt->execute();
    $stmt->close();
    header('Location: reportes.php');
    exit;
}

// Consulta para mostrar reportes con producto vinculado
$sql = "SELECT r.id_repor, r.nombre, r.descripcion, r.fecha_hora, p.nombre AS producto FROM Reportes r LEFT JOIN Productos p ON r.id_prod = p.id_prod";
$result = $db->conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes</title>
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
    <h2 class="mb-4 text-center">Gestión de Reportes</h2>
    <div class="mb-3 d-flex gap-2">
        <input type="text" id="filtroInput" class="form-control w-auto" placeholder="Filtrar por tipo" style="max-width:200px;">
        <button class="btn btn-primary">Filtrar</button>
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Fecha y hora</th>
                <th>Producto vinculado</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nombre']) ?></td>
                <td><?= htmlspecialchars($row['descripcion']) ?></td>
                <td><?= htmlspecialchars($row['fecha_hora']) ?></td>
                <td><?= htmlspecialchars($row['producto']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <div class="mt-4 text-center">
        <button class="btn btn-success" onclick="document.getElementById('formCrear').style.display='block'">Crear nuevo reporte</button>
    </div>
    <div id="formCrear" class="mb-3" style="display:none;">
        <form method="POST" class="d-flex gap-2 align-items-end justify-content-center">
                <input type="text" name="nombre" class="form-control" placeholder="Nombre" required style="max-width:150px;">
                <input type="text" name="descripcion" class="form-control" placeholder="Descripción" required style="max-width:200px;">
                <input type="datetime-local" name="fecha_hora" class="form-control" required style="max-width:180px;">
                <select name="id_prod" class="form-control" required style="max-width:200px;">
                    <option value="">Seleccione producto</option>
                    <?php $prods = $db->conn->query("SELECT id_prod, nombre FROM Productos"); while($prod = $prods->fetch_assoc()): ?>
                        <option value="<?= $prod['id_prod'] ?>"><?= htmlspecialchars($prod['nombre']) ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" name="crear_reporte" class="btn btn-success">Guardar</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('formCrearReporte').reset(); document.getElementById('crearReporteContainer').style.display='none';">Cancelar</button>
        </form>
    </div>
</div>
</body>
</html>
