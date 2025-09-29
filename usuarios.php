<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

require_once 'app/helpers/Database.php';
$db = new Database();
$sql = "SELECT num_doc, tipo_documento, apellidos, nombres, telefono, correo, cargo, rol FROM Users";
$result = $db->conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios</title>
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
    <h2 class="mb-4 text-center">Gestión de Usuarios</h2>
    <div class="mb-3 d-flex gap-2">
        <input type="text" id="filtroInput" class="form-control w-auto" placeholder="Filtrar por nombre" style="max-width:200px;">
        <button class="btn btn-primary">Filtrar</button>
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>N° Documento</th>
                <th>Tipo Documento</th>
                <th>Apellidos</th>
                <th>Nombres</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Cargo</th>
                <th>Rol</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['num_doc']) ?></td>
                <td><?= htmlspecialchars($row['tipo_documento']) ?></td>
                <td><?= htmlspecialchars($row['apellidos']) ?></td>
                <td><?= htmlspecialchars($row['nombres']) ?></td>
                <td><?= htmlspecialchars($row['telefono']) ?></td>
                <td><?= htmlspecialchars($row['correo']) ?></td>
                <td><?= htmlspecialchars($row['cargo']) ?></td>
                <td><?= htmlspecialchars($row['rol']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <div class="mt-4 text-center">
        <button class="btn btn-success">Crear nuevo usuario</button>
    </div>
</div>
</body>
</html>
