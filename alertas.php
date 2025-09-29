<?php
require_once 'app/helpers/Database.php';
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$db = new Database();

// Obtener productos para el formulario de alerta
$productos = $db->conn->query("SELECT id_prod, nombre FROM Productos");

// Procesar formulario de creación de alerta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_alerta'])) {
    $tipo_alerta = $_POST['tipo_alerta'];
    $observacion = $_POST['observacion'];
    $nivel_alerta = $_POST['nivel_alerta'];
    $fecha_generacion = $_POST['fecha_generacion'];
    $estado = $_POST['estado'];
    $id_prod = intval($_POST['id_prod']);
    $stmt = $db->conn->prepare("INSERT INTO Alertas (tipo_alerta, observacion, nivel_alerta, fecha_generacion, estado, id_prod) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssssi', $tipo_alerta, $observacion, $nivel_alerta, $fecha_generacion, $estado, $id_prod);
    $stmt->execute();
    $stmt->close();
    header('Location: alertas.php');
    exit;
}

// Consulta para mostrar alertas con producto vinculado
$sql = "SELECT a.id_alerta, a.tipo_alerta, a.observacion, a.nivel_alerta, a.fecha_generacion, a.estado, p.nombre AS producto FROM Alertas a LEFT JOIN Productos p ON a.id_prod = p.id_prod";
$result = $db->conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alertas</title>
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
    <h2 class="mb-4 text-center">Gestión de Alertas</h2>
    <div class="mb-3 d-flex gap-2">
        <input type="text" id="filtroInput" class="form-control w-auto" placeholder="Filtrar por tipo" style="max-width:200px;">
        <button class="btn btn-primary">Filtrar</button>
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Tipo Alerta</th>
                <th>Observación</th>
                <th>Nivel</th>
                <th>Fecha Generación</th>
                <th>Estado</th>
                <th>Producto vinculado</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['tipo_alerta']) ?></td>
                <td><?= htmlspecialchars($row['observacion']) ?></td>
                <td><?= htmlspecialchars($row['nivel_alerta']) ?></td>
                <td><?= htmlspecialchars($row['fecha_generacion']) ?></td>
                <td><?= htmlspecialchars($row['estado']) ?></td>
                <td><?= htmlspecialchars($row['producto']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <div class="mt-4 text-center">
        <button class="btn btn-success" onclick="document.getElementById('crearAlertaContainer').style.display='block'">Crear nueva alerta</button>
    </div>
<!-- Modal Bootstrap fuera del div principal -->
<div class="modal fade" id="modalCrearAlerta" tabindex="-1" aria-labelledby="modalCrearAlertaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCrearAlertaLabel">Crear alerta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formCrearAlerta">
                    <div class="mb-3">
                        <input type="text" name="tipo_alerta" class="form-control" placeholder="Tipo alerta" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="observacion" class="form-control" placeholder="Observación" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="nivel_alerta" class="form-control" placeholder="Nivel" required>
                    </div>
                    <div class="mb-3">
                        <input type="date" name="fecha_generacion" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="estado" class="form-control" placeholder="Estado" required>
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
                        <button type="submit" name="crear_alerta" class="btn btn-success">Guardar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="document.getElementById('formCrearAlerta').reset();">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>
