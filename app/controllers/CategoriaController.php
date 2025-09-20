<?php
require_once __DIR__ . '/../../app/models/Categoria.php';
require_once __DIR__ . '/../../app/models/Subcategoria.php';
require_once __DIR__ . '/../../app/models/Alerta.php';
class CategoriaController {
    public function index() {
        $categorias = Categoria::getAll();
        include __DIR__ . '/../views/categorias/list.php';
    }
    public function form($id = null) {
        $categoria = $id ? Categoria::getById($id) : null;
        $subcategorias = Subcategoria::getByCategoria($id);
        $alertas = Alerta::getByCategoria($id);
        include __DIR__ . '/../views/categorias/form.php';
    }
}
?>