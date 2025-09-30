<?php
// Vista de listado de salidas para el módulo MVC
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Salidas de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Menú lateral -->
        <nav class="col-md-2 d-none d-md-block bg-dark sidebar vh-100">
            <div class="position-sticky pt-3">
                <h4 class="main-title text-center mb-4" style="color:#fff;">Menú</h4>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="productos.php">Productos</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="categorias.php">Categorías</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="subcategorias.php">Subcategorías</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="proveedores.php">Proveedores</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu active" href="salidas.php">Salidas</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="reportes.php">Reportes</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="alertas.php">Alertas</a></li>
                    <li class="nav-item"><a class="nav-link sidebar-menu" href="usuarios.php">Usuarios</a></li>
                </ul>
            </div>
        </nav>
        <!-- Contenido principal -->
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
                <h2 class="mb-0">Historial de Salidas</h2>
                <div>
                    <a href="dashboard.php" class="btn btn-secondary me-2">Regresar a menú</a>
                    <a href="?form=1" class="btn btn-success">Registrar nueva salida</a>
                </div>
            </div>
            <?php if (!empty($message)): ?>
                <div class="alert alert-success fade show" role="alert"><?= $message ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger fade show" role="alert"><?= $error ?></div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($salidas)): foreach ($salidas as $salida): ?>
                        <tr>
                            <td><?= isset($salida['id_salida']) ? $salida['id_salida'] : (isset($salida['id']) ? $salida['id'] : '') ?></td>
                            <td><?= htmlspecialchars($salida['producto'] ?? 'Producto no encontrado') ?></td>
                            <td><span class="badge bg-info text-dark fs-6"><?= $salida['cantidad'] ?></span></td>
                            <td><?= $salida['fecha'] ?? $salida['fecha_hora'] ?? '' ?></td>
                            <td><?= htmlspecialchars($salida['usuario'] ?? '') ?></td>
                            <td>
                                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'auxiliar'): ?>
                                    <button class="btn btn-outline-primary btn-sm" onclick="openRetornoModal(<?= isset($salida['id_salida']) ? $salida['id_salida'] : (isset($salida['id']) ? $salida['id'] : '') ?>, '<?= htmlspecialchars($salida['producto'] ?? 'Producto no encontrado') ?>')">Retorno a Inventario</button>
                                <?php else: ?>
                                    <a href="?eliminar=<?= isset($salida['id_salida']) ? $salida['id_salida'] : (isset($salida['id']) ? $salida['id'] : '') ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que deseas eliminar esta salida?');">Eliminar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center">No hay salidas registradas.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
