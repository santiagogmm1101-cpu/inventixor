<?php
require_once 'app/helpers/Database.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

// Inicializar $_SESSION['rol'] si no existe
if (!isset($_SESSION['rol'])) {
    if (isset($_SESSION['user']['rol'])) {
        $_SESSION['rol'] = $_SESSION['user']['rol'];
    } else {
        $_SESSION['rol'] = '';
    }
}

$db = new Database();

// Obtener estadísticas del inventario para respuestas contextuales
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM Productos) as total_productos,
                (SELECT COUNT(*) FROM Categoria) as total_categorias,
                (SELECT COUNT(*) FROM Subcategoria) as total_subcategorias,
                (SELECT COUNT(*) FROM Proveedores) as total_proveedores,
                (SELECT COUNT(*) FROM Salidas) as total_salidas,
                (SELECT COUNT(*) FROM Alertas WHERE estado = 'Activa') as alertas_activas,
                (SELECT COUNT(*) FROM Users) as total_usuarios,
                (SELECT SUM(stock) FROM Productos) as stock_total";
$stats_result = $db->conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Manejar consulta AJAX del chat
if (isset($_POST['action']) && $_POST['action'] === 'chat') {
    header('Content-Type: application/json');
    $pregunta = strtolower(trim($_POST['pregunta']));
    
    // Sistema de respuestas inteligentes basado en datos reales
    $respuesta = generarRespuesta($pregunta, $stats, $db, $_SESSION);
    
    echo json_encode(['respuesta' => $respuesta]);
    exit;
}

function generarRespuesta($pregunta, $stats, $db, $session) {
    $rol = $session['rol'];
    $usuario = $session['user']['nombres'] ?? 'Usuario';
    
    // Respuestas contextuales basadas en datos reales
    if (strpos($pregunta, 'productos') !== false || strpos($pregunta, 'inventario') !== false) {
        return "Actualmente tienes <strong>{$stats['total_productos']} productos</strong> registrados en el inventario con un stock total de <strong>{$stats['stock_total']} unidades</strong>. Para gestionar productos ve al módulo <em>Productos</em> donde puedes crear, editar y consultar el inventario.";
    }
    
    if (strpos($pregunta, 'categorias') !== false || strpos($pregunta, 'categorías') !== false) {
        return "Tienes <strong>{$stats['total_categorias']} categorías</strong> y <strong>{$stats['total_subcategorias']} subcategorías</strong> configuradas. Las categorías te ayudan a organizar mejor tu inventario. Puedes gestionarlas desde los módulos <em>Categorías</em> y <em>Subcategorías</em>.";
    }
    
    if (strpos($pregunta, 'proveedores') !== false) {
        return "Hay <strong>{$stats['total_proveedores']} proveedores</strong> registrados en el sistema. Los proveedores son fundamentales para el control de inventario. Gestiona proveedores desde el módulo <em>Proveedores</em>.";
    }
    
    if (strpos($pregunta, 'salidas') !== false || strpos($pregunta, 'fifo') !== false || strpos($pregunta, 'lifo') !== false) {
        return "Se han registrado <strong>{$stats['total_salidas']} salidas</strong> en el sistema. Las salidas permiten controlar automáticamente el stock usando métodos FIFO (primero en entrar, primero en salir) o LIFO (último en entrar, primero en salir). Ve al módulo <em>Salidas</em> para registrar nuevas salidas.";
    }
    
    if (strpos($pregunta, 'alertas') !== false || strpos($pregunta, 'notificaciones') !== false) {
        $mensaje = "Tienes <strong>{$stats['alertas_activas']} alertas activas</strong> que requieren atención. ";
        if ($stats['alertas_activas'] > 0) {
            $mensaje .= "Es importante revisar las alertas para mantener el control del inventario. ";
        }
        $mensaje .= "Gestiona alertas desde el módulo <em>Alertas</em>.";
        return $mensaje;
    }
    
    if (strpos($pregunta, 'usuarios') !== false || strpos($pregunta, 'roles') !== false || strpos($pregunta, 'permisos') !== false) {
        $permisos = '';
        switch($rol) {
            case 'admin':
                $permisos = 'Como <strong>Administrador</strong>, tienes acceso completo a todas las funciones del sistema.';
                break;
            case 'coordinador':
                $permisos = 'Como <strong>Coordinador</strong>, puedes crear y editar registros, pero no eliminar usuarios.';
                break;
            case 'auxiliar':
                $permisos = 'Como <strong>Auxiliar</strong>, tienes permisos de lectura y creación limitada.';
                break;
        }
        return "Hay <strong>{$stats['total_usuarios']} usuarios</strong> en el sistema. $permisos Gestiona usuarios desde el módulo <em>Usuarios</em>.";
    }
    
    if (strpos($pregunta, 'reportes') !== false || strpos($pregunta, 'estadísticas') !== false || strpos($pregunta, 'gráficos') !== false) {
        return "Los reportes te permiten visualizar estadísticas del inventario con gráficos interactivos. Puedes generar reportes de productos, proveedores, salidas y alertas. Accede al módulo <em>Reportes</em> para crear reportes personalizados.";
    }
    
    if (strpos($pregunta, 'hola') !== false || strpos($pregunta, 'ayuda') !== false) {
        return "¡Hola <strong>$usuario</strong>! Soy tu asistente virtual de Inventixor. Puedo ayudarte con información sobre productos, categorías, proveedores, salidas, alertas, usuarios y reportes. ¿Qué necesitas saber?";
    }
    
    if (strpos($pregunta, 'stock') !== false || strpos($pregunta, 'inventario') !== false) {
        return "El stock total actual es de <strong>{$stats['stock_total']} unidades</strong> distribuidas en <strong>{$stats['total_productos']} productos</strong>. Para consultar el stock específico de un producto, ve al módulo <em>Productos</em>.";
    }
    
    // Respuesta por defecto
    return "No tengo información específica sobre esa consulta. Puedo ayudarte con: <em>productos, categorías, proveedores, salidas, alertas, usuarios, reportes, stock</em>. ¿Sobre qué te gustaría saber más?";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistente Virtual Inventixor - IA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: #bdc3c7 !important;
            transition: all 0.3s ease;
            margin: 2px 0;
            border-radius: 8px;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db !important;
            transform: translateX(5px);
        }
        
        .main-content {
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin: 20px;
            padding: 30px;
            min-height: calc(100vh - 40px);
        }
        
        .chat-container {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 20px;
            height: 500px;
            overflow-y: auto;
            margin-bottom: 20px;
            border: 2px solid rgba(102, 126, 234, 0.1);
        }
        
        .chat-message {
            margin-bottom: 20px;
            animation: fadeInUp 0.5s ease-out;
        }
        
        .chat-bubble {
            border-radius: 18px;
            padding: 15px 20px;
            max-width: 80%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: relative;
            word-wrap: break-word;
        }
        
        .chat-bubble.user {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        
        .chat-bubble.bot {
            background: white;
            color: #2c3e50;
            margin-right: auto;
            border: 2px solid #e9ecef;
            border-bottom-left-radius: 5px;
        }
        
        .bot-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 10px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            margin-bottom: 10px;
            margin-left: auto;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .typing-indicator {
            display: none;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .typing-dots {
            display: flex;
            gap: 4px;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            background: #667eea;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }
        
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        
        .chat-input-container {
            background: white;
            border-radius: 25px;
            padding: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 2px solid #e9ecef;
        }
        
        .chat-input {
            border: none;
            outline: none;
            background: transparent;
            padding: 12px 20px;
            font-size: 1rem;
            width: 100%;
        }
        
        .send-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 20px;
            padding: 12px 20px;
            color: white;
            transition: all 0.3s ease;
            min-width: 80px;
        }
        
        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .suggestion-chip {
            background: rgba(102, 126, 234, 0.1);
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 20px;
            padding: 8px 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            color: #667eea;
        }
        
        .suggestion-chip:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 20px;
            color: white;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .page-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 30px;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }
        
        .voice-indicator {
            display: none;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-top: 10px;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .welcome-message {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 20px;
            margin-bottom: 20px;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .feature-item {
            background: white;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .feature-item:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Sidebar Navigation -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar position-fixed h-100">
                <div class="position-sticky pt-4">
                    <div class="text-center mb-4">
                        <h3 class="text-white fw-bold">
                            <i class="fas fa-cube me-2"></i>Inventixor
                        </h3>
                        <p class="text-light opacity-75 small">Sistema de Inventario</p>
                    </div>
                    
                    <ul class="nav flex-column px-2">
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-3"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="productos.php">
                                <i class="fas fa-box me-3"></i>
                                <span>Productos</span>
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="categorias.php">
                                <i class="fas fa-tags me-3"></i>
                                <span>Categorías</span>
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="subcategorias.php">
                                <i class="fas fa-list me-3"></i>
                                <span>Subcategorías</span>
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="proveedores.php">
                                <i class="fas fa-truck me-3"></i>
                                <span>Proveedores</span>
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="salidas.php">
                                <i class="fas fa-sign-out-alt me-3"></i>
                                <span>Salidas</span>
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="reportes.php">
                                <i class="fas fa-chart-bar me-3"></i>
                                <span>Reportes</span>
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="alertas.php">
                                <i class="fas fa-bell me-3"></i>
                                <span>Alertas</span>
                            </a>
                        </li>
                        <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="usuarios.php">
                                <i class="fas fa-users me-3"></i>
                                <span>Usuarios</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3 active" href="ia_ayuda.php">
                                <i class="fas fa-robot me-3"></i>
                                <span>Asistente IA</span>
                            </a>
                        </li>
                    </ul>
                    
                    <div class="mt-auto pt-4 px-2">
                        <div class="bg-light bg-opacity-10 rounded p-3 mb-3">
                            <div class="d-flex align-items-center text-light">
                                <i class="fas fa-user-circle fs-4 me-2"></i>
                                <div>
                                    <div class="fw-semibold"><?php echo $_SESSION['user']['nombres'] ?? 'Usuario'; ?></div>
                                    <small class="opacity-75"><?php echo ucfirst($_SESSION['rol']); ?></small>
                                </div>
                            </div>
                        </div>
                        <a class="nav-link d-flex align-items-center py-2 px-3 text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt me-3"></i>
                            <span>Cerrar Sesión</span>
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 ms-md-auto">
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="page-title">
                            <i class="fas fa-robot me-3"></i>Asistente Virtual IA
                        </h1>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary rounded-pill" onclick="limpiarChat()">
                                <i class="fas fa-refresh me-2"></i>Limpiar Chat
                            </button>
                        </div>
                    </div>

                    <!-- Stats Overview -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <i class="fas fa-box fs-2 mb-2"></i>
                                <h4><?php echo $stats['total_productos']; ?></h4>
                                <p class="mb-0">Productos</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <i class="fas fa-tags fs-2 mb-2"></i>
                                <h4><?php echo $stats['total_categorias']; ?></h4>
                                <p class="mb-0">Categorías</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <i class="fas fa-bell fs-2 mb-2"></i>
                                <h4><?php echo $stats['alertas_activas']; ?></h4>
                                <p class="mb-0">Alertas Activas</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <i class="fas fa-cubes fs-2 mb-2"></i>
                                <h4><?php echo number_format($stats['stock_total']); ?></h4>
                                <p class="mb-0">Stock Total</p>
                            </div>
                        </div>
                    </div>

                    <!-- Welcome Message -->
                    <div class="welcome-message">
                        <h2><i class="fas fa-robot me-2"></i>¡Hola <?php echo $_SESSION['user']['nombres'] ?? 'Usuario'; ?>!</h2>
                        <p class="lead">Soy tu asistente virtual de Inventixor. Puedo ayudarte con información sobre el sistema y responder tus preguntas.</p>
                        
                        <div class="feature-grid">
                            <div class="feature-item">
                                <i class="fas fa-box text-primary fs-3 mb-2"></i>
                                <h6>Productos</h6>
                                <small>Consulta información sobre tu inventario</small>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-chart-line text-success fs-3 mb-2"></i>
                                <h6>Estadísticas</h6>
                                <small>Obtén datos en tiempo real</small>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-bell text-warning fs-3 mb-2"></i>
                                <h6>Alertas</h6>
                                <small>Revisa notificaciones importantes</small>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-question-circle text-info fs-3 mb-2"></i>
                                <h6>Ayuda</h6>
                                <small>Soporte y orientación</small>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Interface -->
                    <div class="row">
                        <div class="col-12">
                            <!-- Chat Container -->
                            <div class="chat-container" id="chatContainer">
                                <div class="chat-message">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="bot-avatar me-3">
                                            <i class="fas fa-robot"></i>
                                        </div>
                                        <div class="chat-bubble bot">
                                            <strong>¡Hola <?php echo $_SESSION['user']['nombres'] ?? 'Usuario'; ?>!</strong><br>
                                            Soy tu asistente virtual de Inventixor. Puedo ayudarte con:
                                            <ul class="mt-2 mb-0">
                                                <li>Información sobre productos y stock</li>
                                                <li>Estado de categorías y proveedores</li>
                                                <li>Alertas y notificaciones</li>
                                                <li>Explicación de funciones del sistema</li>
                                                <li>Estadísticas y reportes</li>
                                            </ul>
                                            ¿En qué puedo asistirte hoy?
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Typing Indicator -->
                            <div class="typing-indicator" id="typingIndicator">
                                <div class="bot-avatar me-3">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div class="typing-dots">
                                    <div class="typing-dot"></div>
                                    <div class="typing-dot"></div>
                                    <div class="typing-dot"></div>
                                </div>
                                <span class="ms-2 text-muted">El asistente está escribiendo...</span>
                            </div>

                            <!-- Voice Indicator -->
                            <div class="voice-indicator text-center" id="voiceIndicator">
                                <i class="fas fa-microphone me-2"></i>Escuchando...
                            </div>

                            <!-- Quick Suggestions -->
                            <div class="suggestions">
                                <div class="suggestion-chip" onclick="enviarSugerencia('¿Cuántos productos tengo?')">
                                    <i class="fas fa-box me-2"></i>Productos
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('¿Qué alertas están activas?')">
                                    <i class="fas fa-bell me-2"></i>Alertas
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('¿Cómo funcionan las salidas FIFO?')">
                                    <i class="fas fa-sign-out-alt me-2"></i>Salidas FIFO
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('¿Qué reportes puedo generar?')">
                                    <i class="fas fa-chart-bar me-2"></i>Reportes
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('¿Cuál es mi rol en el sistema?')">
                                    <i class="fas fa-user me-2"></i>Mi Rol
                                </div>
                            </div>

                            <!-- Chat Input -->
                            <div class="chat-input-container d-flex align-items-center">
                                <input type="text" class="chat-input flex-grow-1" id="chatInput" placeholder="Escribe tu pregunta aquí...">
                                <button class="btn send-btn me-2" onclick="iniciarReconocimientoVoz()" title="Reconocimiento de voz">
                                    <i class="fas fa-microphone"></i>
                                </button>
                                <button class="btn send-btn" onclick="enviarMensaje()" title="Enviar mensaje">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap & jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        let recognition;
        let isListening = false;

        // Función para limpiar el chat
        function limpiarChat() {
            const chatContainer = document.getElementById('chatContainer');
            chatContainer.innerHTML = `
                <div class="chat-message">
                    <div class="d-flex align-items-start mb-3">
                        <div class="bot-avatar me-3">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="chat-bubble bot">
                            <strong>¡Hola <?php echo $_SESSION['user']['nombres'] ?? 'Usuario'; ?>!</strong><br>
                            Chat reiniciado. ¿En qué puedo ayudarte?
                        </div>
                    </div>
                </div>
            `;
        }

        // Función para enviar sugerencias
        function enviarSugerencia(pregunta) {
            document.getElementById('chatInput').value = pregunta;
            enviarMensaje();
        }

        // Función principal para enviar mensajes
        function enviarMensaje() {
            const input = document.getElementById('chatInput');
            const pregunta = input.value.trim();
            
            if (!pregunta) return;

            // Mostrar mensaje del usuario
            mostrarMensajeUsuario(pregunta);
            
            // Limpiar input
            input.value = '';

            // Mostrar indicador de escritura
            mostrarIndicadorEscritura();

            // Simular delay para respuesta más realista
            setTimeout(() => {
                ocultarIndicadorEscritura();
                enviarPreguntaIA(pregunta);
            }, 1000 + Math.random() * 1000);
        }

        // Mostrar mensaje del usuario
        function mostrarMensajeUsuario(pregunta) {
            const chatContainer = document.getElementById('chatContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message';
            messageDiv.innerHTML = `
                <div class="d-flex align-items-end mb-3 justify-content-end">
                    <div class="chat-bubble user me-3">
                        ${pregunta}
                    </div>
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            `;
            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        // Mostrar respuesta del bot
        function mostrarMensajeBot(respuesta) {
            const chatContainer = document.getElementById('chatContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message';
            messageDiv.innerHTML = `
                <div class="d-flex align-items-start mb-3">
                    <div class="bot-avatar me-3">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="chat-bubble bot">
                        ${respuesta}
                    </div>
                </div>
            `;
            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;

            // Síntesis de voz opcional
            if ('speechSynthesis' in window) {
                try {
                    const utterance = new SpeechSynthesisUtterance(respuesta.replace(/<[^>]*>/g, ''));
                    utterance.lang = 'es-ES';
                    utterance.rate = 0.9;
                    utterance.pitch = 1.1;
                    speechSynthesis.speak(utterance);
                } catch (error) {
                    console.log('Síntesis de voz no disponible');
                }
            }
        }

        // Mostrar indicador de escritura
        function mostrarIndicadorEscritura() {
            const indicator = document.getElementById('typingIndicator');
            indicator.style.display = 'flex';
        }

        // Ocultar indicador de escritura
        function ocultarIndicadorEscritura() {
            const indicator = document.getElementById('typingIndicator');
            indicator.style.display = 'none';
        }

        // Enviar pregunta a la IA usando AJAX
        function enviarPreguntaIA(pregunta) {
            $.ajax({
                url: 'ia_ayuda.php',
                method: 'POST',
                data: {
                    action: 'chat',
                    pregunta: pregunta
                },
                dataType: 'json',
                success: function(response) {
                    mostrarMensajeBot(response.respuesta);
                },
                error: function() {
                    mostrarMensajeBot('Lo siento, hubo un error al procesar tu pregunta. Por favor, intenta de nuevo.');
                }
            });
        }

        // Reconocimiento de voz
        function iniciarReconocimientoVoz() {
            if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                alert('Lo siento, tu navegador no soporta reconocimiento de voz.');
                return;
            }

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'es-ES';

            if (!isListening) {
                recognition.start();
                isListening = true;
                document.getElementById('voiceIndicator').style.display = 'block';
            } else {
                recognition.stop();
                isListening = false;
                document.getElementById('voiceIndicator').style.display = 'none';
            }

            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript;
                document.getElementById('chatInput').value = transcript;
                document.getElementById('voiceIndicator').style.display = 'none';
                isListening = false;
            };

            recognition.onerror = function() {
                document.getElementById('voiceIndicator').style.display = 'none';
                isListening = false;
            };

            recognition.onend = function() {
                document.getElementById('voiceIndicator').style.display = 'none';
                isListening = false;
            };
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Enter para enviar mensaje
            document.getElementById('chatInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    enviarMensaje();
                }
            });

            // Focus en el input al cargar la página
            document.getElementById('chatInput').focus();
        });

        // Animaciones suaves para las sugerencias
        document.addEventListener('DOMContentLoaded', function() {
            const suggestions = document.querySelectorAll('.suggestion-chip');
            suggestions.forEach((suggestion, index) => {
                suggestion.style.animationDelay = `${index * 0.1}s`;
                suggestion.classList.add('animate__animated', 'animate__fadeInUp');
            });
        });
    </script>

    <!-- Animate.css para animaciones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</body>
</html>
