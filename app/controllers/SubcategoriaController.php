<?php
require_once __DIR__ . '/../../app/models/Subcategoria.php';
require_once __DIR__ . '/../../app/models/Producto.php';
require_once __DIR__ . '/../../app/models/Alerta.php';
class SubcategoriaController {
    public function index() {
        $subcategorias = Subcategoria::getAll();
        include __DIR__ . '/../views/subcategorias/list.php';
    }
    public function form($id = null) {
        $subcategoria = $id ? Subcategoria::getById($id) : null;
        $productos = Producto::getBySubcategoria($id);
        $alertas = Alerta::getBySubcategoria($id);
        include __DIR__ . '/../views/subcategorias/form.php';
    }
}
?>