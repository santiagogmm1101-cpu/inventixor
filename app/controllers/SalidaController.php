<?php
require_once __DIR__ . '/../../app/models/Salida.php';
require_once __DIR__ . '/../../app/models/Producto.php';
class SalidaController {
    public function index() {
        $salidas = Salida::getAll();
        include __DIR__ . '/../views/salidas/list.php';
    }
    public function form($id = null) {
        $salida = $id ? Salida::getById($id) : null;
        $productos = [];
        if ($id) {
            $productos = Salida::getProductos($id);
        }
        include __DIR__ . '/../views/salidas/form.php';
    }
}
?>