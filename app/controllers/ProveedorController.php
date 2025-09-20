<?php
require_once __DIR__ . '/../../app/models/Proveedor.php';
require_once __DIR__ . '/../../app/models/Alerta.php';
require_once __DIR__ . '/../../app/models/Reporte.php';
class ProveedorController {
    public function index() {
        $proveedores = Proveedor::getAll();
        include __DIR__ . '/../views/proveedores/list.php';
    }
    public function form($id = null) {
        $proveedor = $id ? Proveedor::getById($id) : null;
        $alertas = Alerta::getByProveedor($id);
        $reportes = Reporte::getByProveedor($id);
        include __DIR__ . '/../views/proveedores/form.php';
    }
}
?>