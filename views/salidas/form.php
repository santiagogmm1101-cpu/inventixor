<?php
// Vista de formulario para registrar/modificar salidas
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Salida</title>
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
                <h2 class="mb-0"><?= isset($salida) ? 'Modificar Salida' : 'Registrar Salida' ?></h2>
                <a href="salidas.php" class="btn btn-secondary">Volver al listado</a>
            </div>
            <?php if (!empty($message)): ?>
                <div class="alert alert-success fade show" role="alert"><?= $message ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger fade show" role="alert"><?= $error ?></div>
            <?php endif; ?>
            <form method="post" class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="producto_id" class="form-label">Producto</label>
                    <select class="form-select" id="producto_id" name="producto_id" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($productos as $prod): ?>
                            <option value="<?= $prod['id_prod'] ?? $prod['id'] ?>" <?= isset($salida) && ($salida['producto_id'] ?? $salida['id_prod'] ?? null) == ($prod['id_prod'] ?? $prod['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prod['nombre']) ?> (Stock: <?= $prod['stock'] ?? '' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="cantidad" class="form-label">Cantidad</label>
                    <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" value="<?= $salida['cantidad'] ?? '' ?>" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <?= isset($salida) ? 'Actualizar' : 'Registrar' ?>
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
