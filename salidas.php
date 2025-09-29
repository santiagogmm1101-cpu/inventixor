<?php
require_once 'app/helpers/Database.php';
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$db = new Database();

// Obtener productos para el formulario de salida
$productos = $db->conn->query("SELECT id_prod, nombre FROM Productos");

// Procesar formulario de creación de salida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_salida'])) {
    $tipo_salida = $_POST['tipo_salida'];
    $fecha_hora = $_POST['fecha_hora'];
    $cantidad = $_POST['cantidad'];
    $observacion = $_POST['observacion'];
    $id_prod = intval($_POST['id_prod']);
    $stmt = $db->conn->prepare("INSERT INTO Salidas (tipo_salida, fecha_hora, cantidad, observacion, id_prod) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('ssisi', $tipo_salida, $fecha_hora, $cantidad, $observacion, $id_prod);
    $stmt->execute();
    $stmt->close();
    header('Location: salidas.php');
    exit;
}

// Consulta para mostrar salidas con producto vinculado
$sql = "SELECT s.id_salida, s.tipo_salida, s.fecha_hora, s.cantidad, s.observacion, p.nombre AS producto FROM Salidas s LEFT JOIN Productos p ON s.id_prod = p.id_prod";
$result = $db->conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Salidas</title>
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
    <h2 class="mb-4 text-center">Gestión de Salidas</h2>
    <div class="mb-3 d-flex gap-2">
        <input type="text" id="filtroInput" class="form-control w-auto" placeholder="Filtrar por tipo" style="max-width:200px;">
        <button class="btn btn-primary">Filtrar</button>
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Tipo Salida</th>
                <th>Fecha y Hora</th>
                <th>Cantidad</th>
                <th>Observación</th>
                <th>Producto vinculado</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['tipo_salida']) ?></td>
                <td><?= htmlspecialchars($row['fecha_hora']) ?></td>
                <td><?= htmlspecialchars($row['cantidad']) ?></td>
                <td><?= htmlspecialchars($row['observacion']) ?></td>
                <td><?= htmlspecialchars($row['producto']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <div class="mt-4 text-center">
        <button class="btn btn-success" onclick="document.getElementById('crearSalidaContainer').style.display='block'">Crear nueva salida</button>
    </div>
<!-- Modal Bootstrap fuera del div principal -->
<div class="modal fade" id="modalCrearSalida" tabindex="-1" aria-labelledby="modalCrearSalidaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCrearSalidaLabel">Crear salida</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formCrearSalida">
                    <div class="mb-3">
                        <input type="text" name="tipo_salida" class="form-control" placeholder="Tipo salida" required>
                    </div>
                    <div class="mb-3">
                        <input type="datetime-local" name="fecha_hora" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="cantidad" class="form-control" placeholder="Cantidad" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="observacion" class="form-control" placeholder="Observación" required>
                    </div>
                    <div class="mb-3">
                        <select name="id_prod" class="form-control" required>
                            <option value="">Seleccione producto</option>
                            <?php $prods = $db->conn->query("SELECT id_prod, nombre FROM Productos"); while($prod = $prods->fetch_assoc()): ?>
                                <option value="<?= $prod['id_prod'] ?>"><?= htmlspecialchars($prod['nombre']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="submit" name="crear_salida" class="btn btn-success">Guardar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="document.getElementById('formCrearSalida').reset();">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>
