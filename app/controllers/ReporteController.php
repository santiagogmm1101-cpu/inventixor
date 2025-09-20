<?php
require_once __DIR__ . '/../../app/models/Reporte.php';
require_once __DIR__ . '/../../app/models/Producto.php';
require_once __DIR__ . '/../../app/models/Proveedor.php';
class ReporteController {
    public function index() {
        $reportes = Reporte::getAll();
        include __DIR__ . '/../views/reportes/list.php';
    }
    public function form($id = null) {
        $reporte = $id ? Reporte::getById($id) : null;
        $productos = Producto::getAll();
        $proveedores = Proveedor::getAll();
        include __DIR__ . '/../views/reportes/form.php';
    }
}
?>