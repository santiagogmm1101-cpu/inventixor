    // Crear producto
    public function crear($data) {
        $id_prod = Producto::crear($data); // Suponiendo que retorna el id
        // Registrar alerta de creación
        Alerta::registrarAlerta([
            'tipo_alerta' => 'Creación',
            'observacion' => 'Producto creado por usuario',
            'nivel_alerta' => 'info',
            'estado' => 'activo',
            'id_prod' => $id_prod
        ]);
    }

    // Editar producto
    public function editar($id, $data) {
        Producto::editar($id, $data);
        // Registrar alerta de edición
        Alerta::registrarAlerta([
            'tipo_alerta' => 'Edición',
            'observacion' => 'Producto editado por usuario',
            'nivel_alerta' => 'info',
            'estado' => 'activo',
            'id_prod' => $id
        ]);
    }

    // Eliminar producto
    public function eliminar($id) {
        Producto::eliminar($id);
        // Registrar alerta de eliminación
        Alerta::registrarAlerta([
            'tipo_alerta' => 'Eliminación',
            'observacion' => 'Producto eliminado por usuario',
            'nivel_alerta' => 'warning',
            'estado' => 'inactivo',
            'id_prod' => $id
        ]);
    }

    // Cambio de estado
    public function cambiarEstado($id, $nuevoEstado) {
        Producto::cambiarEstado($id, $nuevoEstado);
        // Registrar alerta de cambio de estado
        Alerta::registrarAlerta([
            'tipo_alerta' => 'Cambio de estado',
            'observacion' => 'Estado cambiado a ' . $nuevoEstado,
            'nivel_alerta' => 'info',
            'estado' => $nuevoEstado,
            'id_prod' => $id
        ]);
    }

    // Alerta por stock bajo/alto
    public function verificarStock($id) {
        $producto = Producto::getById($id);
        $stock = intval($producto['stock']);
        if ($stock < 10) {
            Alerta::registrarAlerta([
                'tipo_alerta' => 'Stock bajo',
                'observacion' => 'Stock bajo detectado',
                'nivel_alerta' => 'danger',
                'estado' => 'activo',
                'id_prod' => $id
            ]);
        } elseif ($stock > 1000) {
            Alerta::registrarAlerta([
                'tipo_alerta' => 'Stock alto',
                'observacion' => 'Stock alto detectado',
                'nivel_alerta' => 'info',
                'estado' => 'activo',
                'id_prod' => $id
            ]);
        }
    }
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