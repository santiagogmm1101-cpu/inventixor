<?php
require_once __DIR__ . '/../../app/models/Alerta.php';
require_once __DIR__ . '/../../app/models/Categoria.php';
require_once __DIR__ . '/../../app/models/Subcategoria.php';
require_once __DIR__ . '/../../app/models/Producto.php';
require_once __DIR__ . '/../../app/models/Proveedor.php';
class AlertaController {
    public function index() {
        $alertas = Alerta::getAll();
        include __DIR__ . '/../views/alertas/list.php';
    }
    public function form($id = null) {
        $alerta = $id ? Alerta::getById($id) : null;
        $categorias = Categoria::getAll();
        $subcategorias = Subcategoria::getAll();
        $productos = Producto::getAll();
        $proveedores = Proveedor::getAll();
        include __DIR__ . '/../views/alertas/form.php';
    }
}
?>