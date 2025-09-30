<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salidas de Inventario</title>
    <link href="/public/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <!-- DEBUG: Mostrar sesión -->
    <pre style="background:#f8f9fa;border:1px solid #ccc;padding:8px;">SESSION: <?php print_r($_SESSION); ?></pre>
    <h2 class="mb-4">Registrar Salida</h2>
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
                    <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['nombre']) ?> (Stock: <?= $prod['stock'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="cantidad" class="form-label">Cantidad</label>
            <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" required>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Registrar</button>
        </div>
    </form>
    <h3 class="mb-3">Historial de Salidas</h3>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($salidas as $salida): ?>
                <tr>
                    <td><?= $salida['id'] ?></td>
                    <td><?= htmlspecialchars($salida['producto']) ?></td>
                    <td><span class="badge bg-info text-dark"><?= $salida['cantidad'] ?></span></td>
                    <td><?= $salida['fecha'] ?></td>
                    <td><?= htmlspecialchars($salida['usuario']) ?></td>
                    <td>
                        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'auxiliar'): ?>
                            <button class="btn btn-outline-primary btn-sm" onclick="openRetornoModal(<?= $salida['id'] ?>, '<?= htmlspecialchars($salida['producto']) ?>')">Retorno a Inventario</button>
                        <?php else: ?>
                            <a href="salidas.php?eliminar=<?= $salida['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que deseas eliminar esta salida?')">Eliminar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

<!-- Modal Retorno a Inventario -->
<div class="modal fade" id="retornoModal" tabindex="-1" aria-labelledby="retornoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="retornoModalLabel">Solicitar Retorno a Inventario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="retornoForm">
                    <input type="hidden" id="retornoSalidaId" name="salida_id">
                    <div class="mb-3">
                        <label for="retornoProducto" class="form-label">Producto</label>
                        <input type="text" class="form-control" id="retornoProducto" name="producto" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="retornoMotivo" class="form-label">Motivo del Retorno</label>
                        <textarea class="form-control" id="retornoMotivo" name="motivo" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="enviarRetorno()">Solicitar autorización</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openRetornoModal(salidaId, producto) {
        document.getElementById('retornoSalidaId').value = salidaId;
        document.getElementById('retornoProducto').value = producto;
        document.getElementById('retornoMotivo').value = '';
        var modal = new bootstrap.Modal(document.getElementById('retornoModal'));
        modal.show();
}

function enviarRetorno() {
        var salidaId = document.getElementById('retornoSalidaId').value;
        var motivo = document.getElementById('retornoMotivo').value;
        if (!motivo.trim()) {
                alert('Debes ingresar el motivo del retorno.');
                return;
        }
        var formData = new FormData();
        formData.append('solicitar_retorno', '1');
        formData.append('salida_id', salidaId);
        formData.append('motivo', motivo);
        fetch('salidas.php', {
                method: 'POST',
                body: formData
        })
        .then(response => response.json())
        .then(data => {
                if (data.success) {
                        alert('Solicitud enviada correctamente.');
                        location.reload();
                } else {
                        alert('Error: ' + (data.error || 'No se pudo enviar la solicitud.'));
                }
        })
        .catch(() => alert('Error de red al enviar la solicitud.'));
}
</script>
</body>
</html>
