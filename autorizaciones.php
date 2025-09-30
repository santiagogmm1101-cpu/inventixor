<?php
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['rol'] !== 'coordinador' && $_SESSION['rol'] !== 'admin')) {
    header('Location: index.php');
    exit;
}
require_once 'app/helpers/Database.php';
$db = new Database();

// Aprobar o rechazar solicitud
if (isset($_GET['aprobar'])) {
    $id = intval($_GET['aprobar']);
    $usuario_autoriza = $_SESSION['user'];
    $db->conn->query("UPDATE Autorizaciones SET estado='aprobada', usuario_autoriza='$usuario_autoriza', fecha_respuesta=NOW() WHERE id=$id");
    $successMsg = '<div class="alert alert-success alert-dismissible fade show" role="alert">Solicitud aprobada.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}
if (isset($_GET['rechazar'])) {
    $id = intval($_GET['rechazar']);
    $usuario_autoriza = $_SESSION['user'];
    $db->conn->query("UPDATE Autorizaciones SET estado='rechazada', usuario_autoriza='$usuario_autoriza', fecha_respuesta=NOW() WHERE id=$id");
    $successMsg = '<div class="alert alert-warning alert-dismissible fade show" role="alert">Solicitud rechazada.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}

// Consultar solicitudes pendientes
$autorizaciones = $db->conn->query("
    SELECT a.*, u.nombres, u.apellidos, u.correo
    FROM Autorizaciones a
    JOIN Users u ON a.usuario_solicita = u.num_doc
    WHERE a.estado='pendiente'
    ORDER BY a.fecha_solicitud DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Autorizaciones Pendientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .main-title { font-size: 2.2rem; font-family: 'Inter', Arial, sans-serif; font-weight: 700; color: #263238; }
        .sidebar-menu { font-size: 1.1rem; font-family: 'Inter', Arial, sans-serif; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-dark sidebar vh-100">
            <div class="position-sticky pt-3">
                <h4 class="main-title text-center mb-4" style="color:#fff;">Menú</h4>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="productos.php">Productos</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="categorias.php">Categorías</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="subcategorias.php">Subcategorías</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="proveedores.php">Proveedores</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="salidas.php">Salidas</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="reportes.php">Reportes</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="alertas.php">Alertas</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="usuarios.php">Usuarios</a></li>
                    <?php if ($_SESSION['rol'] === 'coordinador' || $_SESSION['rol'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link sidebar-menu active" href="autorizaciones.php"><span class="badge bg-warning text-dark me-2" title="Solicitudes pendientes"><i class="bi bi-shield-check"></i></span>Autorizaciones</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                <h1 class="main-title" style="margin:0;">Autorizaciones Pendientes</h1>
                <a href="dashboard.php" class="btn btn-secondary">Regresar a menú</a>
            </div>
            <?php if(isset($successMsg)) echo $successMsg; ?>
            <div class="card mb-4">
                <div class="card-header">Solicitudes</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Módulo</th>
                                    <th>ID Registro</th>
                                    <th>Solicitante</th>
                                    <th>Fecha Solicitud</th>
                                    <th>Comentario</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $autorizaciones->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><span class="badge bg-info text-dark" title="<?= htmlspecialchars($row['modulo']) ?>"><?= htmlspecialchars(ucfirst($row['modulo'])) ?></span></td>
                                    <td><?= $row['id_registro'] ?></td>
                                    <td>
                                        <span class="badge bg-secondary" title="Usuario solicitante">
                                            <?= htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) ?><br>
                                            <small><?= htmlspecialchars($row['correo']) ?></small>
                                        </span>
                                    </td>
                                    <td><?= $row['fecha_solicitud'] ?></td>
                                    <td><?= htmlspecialchars($row['comentario']) ?></td>
                                    <td><span class="badge bg-warning text-dark">Pendiente</span></td>
                                    <td>
                                        <a href="autorizaciones.php?aprobar=<?= $row['id'] ?>" class="btn btn-success btn-sm" title="Aprobar"><i class="bi bi-check-circle"></i> Aprobar</a>
                                        <a href="autorizaciones.php?rechazar=<?= $row['id'] ?>" class="btn btn-danger btn-sm" title="Rechazar"><i class="bi bi-x-circle"></i> Rechazar</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<!-- Vista provisional. Si se elimina este archivo, el resto del sistema no se ve afectado. -->
