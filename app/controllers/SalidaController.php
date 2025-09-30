<?php
require_once __DIR__ . '/../../app/models/Salida.php';
require_once __DIR__ . '/../../app/models/Producto.php';
class SalidaController {
    public function handleRequest() {
        $message = '';
        $error = '';
        // Eliminar salida si se solicita
        if (isset($_GET['eliminar'])) {
            $id = intval($_GET['eliminar']);
            $ok = \Salida::eliminarSalida($id);
            if ($ok) {
                $message = 'Salida eliminada correctamente.';
            } else {
                $error = 'No se pudo eliminar la salida.';
            }
        }

        // Solicitud de retorno a inventario (AJAX)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar_retorno'])) {
            header('Content-Type: application/json');
            $salida_id = intval($_POST['salida_id'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? '');
            $usuario = $_SESSION['user']['nombres'] ?? 'Desconocido';
            if ($salida_id && $motivo) {
                // Obtener producto
                $salida = \Salida::getById($salida_id);
                if ($salida) {
                    $id_prod = $salida['id_prod'];
                    // Registrar alerta para coordinador y administrador
                    require_once __DIR__ . '/../models/Alerta.php';
                    $alerta = [
                        'id_prod' => $id_prod,
                        'motivo' => $motivo,
                        'usuario' => $usuario,
                        'tipo' => 'retorno',
                        'destinatarios' => ['coordinador', 'administrador']
                    ];
                    \Alerta::registrarRetorno($alerta);
                    echo json_encode(['success' => true]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'error' => 'Salida no encontrada.']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $producto_id = $_POST['producto_id'] ?? null;
            $cantidad = $_POST['cantidad'] ?? null;
            if ($producto_id && $cantidad) {
                $producto = \Producto::getById($producto_id);
                if (!$producto) {
                    $error = 'Producto no encontrado.';
                } elseif ($producto['stock'] < $cantidad) {
                    $error = 'Stock insuficiente para realizar la salida.';
                } else {
                    // Registrar salida y actualizar stock
                    $ok = \Salida::registrarSalida($producto_id, $cantidad);
                    if ($ok) {
                        // --- Generar reporte automáticamente al registrar la salida ---
                        require_once __DIR__ . '/../models/Reporte.php';
                        $nombre_reporte = 'Salida de producto';
                        $descripcion = 'Salida de producto ID ' . $producto_id . ' por cantidad ' . $cantidad;
                        $num_doc = $_SESSION['user']['num_doc'] ?? null;
                        $id_nit = $producto['id_nit'] ?? null;
                        $id_prod = $producto_id;
                        // Se registra el reporte en la base de datos
                        $reporte_ok = \Reporte::registrarReporte($nombre_reporte, $descripcion, $num_doc, $id_nit, $id_prod);
                        if ($reporte_ok) {
                            $message = 'Salida registrada y reporte generado correctamente.';
                        } else {
                            $message = 'Salida registrada, pero hubo un error al generar el reporte.';
                        }
                    } else {
                        $error = 'Error al registrar la salida.';
                    }
                }
            } else {
                $error = 'Todos los campos son obligatorios.';
            }
        }
        if (isset($_GET['form'])) {
            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            $this->form($id, $message, $error);
        } else {
            $this->index($message, $error);
        }
    }
    public function index($message = '', $error = '') {
        $salidas = Salida::getAll();
    $viewPath = realpath(__DIR__ . '/../../views/salidas/list.php');
        if ($viewPath && file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo '<div class="alert alert-danger">No se encontró la vista de listado de salidas en: ' . __DIR__ . '/../views/salidas/list.php' . '</div>';
        }
    }
    public function form($id = null, $message = '', $error = '') {
        $salida = $id ? Salida::getById($id) : null;
        // Siempre mostrar todos los productos para el select
        $productos = \Producto::getAll();
    $formPath = realpath(__DIR__ . '/../../views/salidas/form.php');
        if ($formPath && file_exists($formPath)) {
            include $formPath;
        } else {
            echo '<div class="alert alert-danger">No se encontró la vista de formulario de salidas en: ' . __DIR__ . '/../views/salidas/form.php' . '</div>';
        }
    }
}
?>