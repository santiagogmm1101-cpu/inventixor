<?php
require_once __DIR__ . '/../../app/models/Producto.php';
require_once __DIR__ . '/../../app/models/Proveedor.php';
require_once __DIR__ . '/../../app/models/Salida.php';
require_once __DIR__ . '/../../app/models/Alerta.php';
require_once __DIR__ . '/../../app/models/Reporte.php';
class ProductosController {
    public function index() {
        $productos = Producto::getAll();
        include __DIR__ . '/../views/productos/list.php';
    }
    public function form($id = null) {
        $producto = $id ? Producto::getById($id) : null;
        $proveedores = Proveedor::getByProducto($id);
        $salidas = Salida::getByProducto($id);
        $alertas = Alerta::getByProducto($id);
        $reportes = Reporte::getByProducto($id);
        include __DIR__ . '/../views/productos/form.php';
    }
}
?>