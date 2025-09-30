<?php
require_once 'app/helpers/Database.php';
require_once 'app/models/Salida.php';
$db = new Database();
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$rol = $_SESSION['rol'] ?? '';
$salidas = Salida::getAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Salidas de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4">Reportes de Salidas de Productos</h1>
    <a href="reportes.php" class="btn btn-secondary mb-3">Volver a reportes</a>
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Fecha y hora</th>
                <th>Tipo de salida</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($salidas as $salida): ?>
            <tr>
                <td><?= htmlspecialchars($salida['id_salida']) ?></td>
                <td><?= htmlspecialchars($salida['producto']) ?></td>
                <td><?= htmlspecialchars($salida['cantidad']) ?></td>
                <td><?= htmlspecialchars($salida['fecha_hora']) ?></td>
                <td><?= htmlspecialchars($salida['tipo_salida'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($salida['observacion'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
