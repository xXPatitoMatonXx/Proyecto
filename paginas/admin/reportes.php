<?php
/**
 * Reportes Generales - Administrador
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

session_start();
require_once '../../config/conexion.php';
require_once '../../config/config.php';
require_once '../../funciones/sesiones.php';

// Verificar que sea administrador
requerir_admin();

$usuario_admin = obtener_usuario_actual();
$mensaje = '';

// Parámetros del reporte
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$año = isset($_GET['año']) ? (int)$_GET['año'] : date('Y');
$tipo_reporte = $_GET['tipo'] ?? 'general';

// Validar parámetros
if ($mes < 1 || $mes > 12) $mes = date('n');
if ($año < 2020 || $año > 2030) $año = date('Y');

// Nombres de meses
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Funciones locales para reportes
function obtener_datos_todos_usuarios_local($mes, $año, $conexion) {
    $query = "SELECT 
                u.id_usuario,
                u.nombre_completo,
                u.rfc,
                COUNT(rb.id_registro) as total_sesiones,
                COALESCE(SUM(rb.horas_bombeadas), 0) as total_horas,
                COALESCE(SUM(rb.litros_bombeados), 0) as total_litros
              FROM usuarios u 
              LEFT JOIN registro_bombeo rb ON u.id_usuario = rb.id_usuario 
                AND MONTH(rb.fecha_bombeo) = ? 
                AND YEAR(rb.fecha_bombeo) = ?
              WHERE u.id_rol = 2 AND u.activo = 1
              GROUP BY u.id_usuario, u.nombre_completo, u.rfc
              ORDER BY u.nombre_completo ASC";
    
    $stmt = $conexion->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ii", $mes, $año);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

function obtener_datos_bombeo_usuario_local($usuario_id, $mes, $año, $conexion) {
    $query = "SELECT rb.*, u.nombre_completo, u.rfc 
              FROM registro_bombeo rb 
              INNER JOIN usuarios u ON rb.id_usuario = u.id_usuario 
              WHERE rb.id_usuario = ? 
              AND MONTH(rb.fecha_bombeo) = ? 
              AND YEAR(rb.fecha_bombeo) = ?
              ORDER BY rb.fecha_bombeo ASC";
    
    $stmt = $conexion->prepare($query);
    if ($stmt) {
        $stmt->bind_param("iii", $usuario_id, $mes, $año);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

function obtener_resumen_usuario_local($usuario_id, $mes, $año, $conexion) {
    $query = "SELECT 
                COUNT(rb.id_registro) as total_sesiones,
                COALESCE(SUM(rb.horas_bombeadas), 0) as total_horas,
                COALESCE(SUM(rb.litros_bombeados), 0) as total_litros,
                u.nombre_completo,
                u.rfc
              FROM usuarios u 
              LEFT JOIN registro_bombeo rb ON u.id_usuario = rb.id_usuario 
                AND MONTH(rb.fecha_bombeo) = ? 
                AND YEAR(rb.fecha_bombeo) = ?
              WHERE u.id_usuario = ?";
    
    $stmt = $conexion->prepare($query);
    if ($stmt) {
        $stmt->bind_param("iii", $mes, $año, $usuario_id);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        
        if ($resultado) {
            return [
                'total_sesiones' => (int)$resultado['total_sesiones'],
                'total_horas' => (float)$resultado['total_horas'],
                'total_litros' => (int)$resultado['total_litros'],
                'nombre_completo' => $resultado['nombre_completo'],
                'rfc' => $resultado['rfc']
            ];
        }
    }
    
    // Si no hay datos, buscar info básica del usuario
    $query_usuario = "SELECT nombre_completo, rfc FROM usuarios WHERE id_usuario = ?";
    $stmt_usuario = $conexion->prepare($query_usuario);
    if ($stmt_usuario) {
        $stmt_usuario->bind_param("i", $usuario_id);
        $stmt_usuario->execute();
        $usuario_data = $stmt_usuario->get_result()->fetch_assoc();
        
        return [
            'total_sesiones' => 0,
            'total_horas' => 0,
            'total_litros' => 0,
            'nombre_completo' => $usuario_data['nombre_completo'] ?? 'Usuario no encontrado',
            'rfc' => $usuario_data['rfc'] ?? 'N/A'
        ];
    }
    
    return [
        'total_sesiones' => 0,
        'total_horas' => 0,
        'total_litros' => 0,
        'nombre_completo' => 'Usuario no encontrado',
        'rfc' => 'N/A'
    ];
}

// Inicializar variables
$datos_reporte = [];
$datos_bombeo = [];
$resumen_usuario = [];

// Generar PDF si se solicita
if (isset($_POST['generar_pdf'])) {
    $mensaje = "Función de generación de PDF en desarrollo. Los datos se muestran en pantalla.";
}

// Obtener datos según el tipo de reporte
if ($tipo_reporte == 'general') {
    $datos_reporte = obtener_datos_todos_usuarios_local($mes, $año, $conexion);
    $titulo_reporte = "Reporte General - " . $meses[$mes] . " " . $año;
} else {
    $usuario_id = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
    if ($usuario_id > 0) {
        $datos_bombeo = obtener_datos_bombeo_usuario_local($usuario_id, $mes, $año, $conexion);
        $resumen_usuario = obtener_resumen_usuario_local($usuario_id, $mes, $año, $conexion);
        $titulo_reporte = "Reporte Individual - " . $resumen_usuario['nombre_completo'] . " - " . $meses[$mes] . " " . $año;
    }
}

// Obtener lista de usuarios para selector
$query_usuarios = "SELECT id_usuario, nombre_completo, rfc FROM usuarios WHERE id_rol = 2 AND activo = 1 ORDER BY nombre_completo";
$lista_usuarios = $conexion->query($query_usuarios);
if ($lista_usuarios) {
    $lista_usuarios = $lista_usuarios->fetch_all(MYSQLI_ASSOC);
} else {
    $lista_usuarios = [];
}

// Estadísticas generales del mes
$query_stats = "SELECT 
    COUNT(DISTINCT rb.id_usuario) as usuarios_activos,
    COUNT(rb.id_registro) as total_bombeos,
    COALESCE(SUM(rb.horas_bombeadas), 0) as total_horas,
    COALESCE(SUM(rb.litros_bombeados), 0) as total_litros,
    COALESCE(AVG(rb.horas_bombeadas), 0) as promedio_horas,
    COALESCE(AVG(rb.litros_bombeados), 0) as promedio_litros
    FROM registro_bombeo rb 
    WHERE MONTH(rb.fecha_bombeo) = ? AND YEAR(rb.fecha_bombeo) = ?";
$stmt_stats = $conexion->prepare($query_stats);
if ($stmt_stats) {
    $stmt_stats->bind_param("ii", $mes, $año);
    $stmt_stats->execute();
    $estadisticas = $stmt_stats->get_result()->fetch_assoc();
} else {
    $estadisticas = [
        'usuarios_activos' => 0,
        'total_bombeos' => 0,
        'total_horas' => 0,
        'total_litros' => 0,
        'promedio_horas' => 0,
        'promedio_litros' => 0
    ];
}

$titulo_pagina = "Reportes del Sistema";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> - Sistema GHC</title>
    <link rel="stylesheet" href="../../css/estilos.css">
    <style>
        .reporte-preview {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 30px;
            margin: 20px 0;
            font-family: Arial, sans-serif;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .reporte-header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .estadistica-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 15px;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .reporte-preview {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header no-print">
        <div class="header-content">
            <div class="logo">
                <h1>Sistema GHC - Administrador</h1>
            </div>
            <div class="user-info">
                <span>👤 <?php echo htmlspecialchars($usuario_admin['nombre']); ?> (Admin)</span>
                <a href="../../logout.php" class="btn btn-secondary btn-small">Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <nav class="sidebar no-print">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="inicio_admin.php" class="nav-link">📊 Panel Administrativo</a>
            </li>
            <li class="nav-item">
                <a href="usuarios.php" class="nav-link">👥 Gestión de Usuarios</a>
            </li>
            <li class="nav-item">
                <a href="solicitudes.php" class="nav-link">📋 Solicitudes de Bombeo</a>
            </li>
            <li class="nav-item">
                <a href="registro_bombeo.php" class="nav-link">⚙️ Registro de Bombeo</a>
            </li>
            <li class="nav-item">
                <a href="reportes.php" class="nav-link active">📄 Reportes</a>
            </li>
        </ul>
    </nav>

    <!-- Contenido Principal -->
    <main class="main-content">
        <div class="container">
            
            <!-- Controles (solo visible en pantalla) -->
            <div class="no-print">
                <h1>📄 Reportes del Sistema</h1>
                <p class="mb-4">Genera y consulta reportes de uso del pozo comunitario.</p>

                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas del mes -->
                <div class="card mb-4">
                    <div class="card-header">
                        📊 Estadísticas del Mes: <?php echo $meses[$mes] . ' ' . $año; ?>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                            <div class="estadistica-card">
                                <h3 style="color: #007bff; margin: 0;"><?php echo $estadisticas['usuarios_activos']; ?></h3>
                                <p>Usuarios Activos</p>
                            </div>
                            <div class="estadistica-card">
                                <h3 style="color: #28a745; margin: 0;"><?php echo $estadisticas['total_bombeos']; ?></h3>
                                <p>Total Bombeos</p>
                            </div>
                            <div class="estadistica-card">
                                <h3 style="color: #ffc107; margin: 0;"><?php echo number_format($estadisticas['total_horas'], 1); ?></h3>
                                <p>Horas Totales</p>
                            </div>
                            <div class="estadistica-card">
                                <h3 style="color: #17a2b8; margin: 0;"><?php echo number_format($estadisticas['total_litros']); ?></h3>
                                <p>Litros Totales</p>
                            </div>
                            <div class="estadistica-card">
                                <h3 style="color: #6f42c1; margin: 0;"><?php echo number_format($estadisticas['promedio_horas'], 1); ?></h3>
                                <p>Promedio Horas/Sesión</p>
                            </div>
                            <div class="estadistica-card">
                                <h3 style="color: #fd7e14; margin: 0;"><?php echo number_format($estadisticas['promedio_litros']); ?></h3>
                                <p>Promedio Litros/Sesión</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selector de reporte -->
                <div class="card mb-4">
                    <div class="card-header">
                        ⚙️ Configuración del Reporte
                    </div>
                    <div class="card-body">
                        <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                            <div class="input-group">
                                <label for="tipo">Tipo de Reporte:</label>
                                <select id="tipo" name="tipo" onchange="toggleUsuarioSelector()">
                                    <option value="general" <?php echo $tipo_reporte == 'general' ? 'selected' : ''; ?>>Reporte General</option>
                                    <option value="individual" <?php echo $tipo_reporte == 'individual' ? 'selected' : ''; ?>>Reporte Individual</option>
                                </select>
                            </div>
                            
                            <div class="input-group" id="selectorUsuario" style="<?php echo $tipo_reporte == 'general' ? 'display: none;' : ''; ?>">
                                <label for="usuario_id">Usuario:</label>
                                <select id="usuario_id" name="usuario_id">
                                    <option value="">Seleccionar usuario</option>
                                    <?php foreach ($lista_usuarios as $usuario): ?>
                                        <option value="<?php echo $usuario['id_usuario']; ?>" 
                                                <?php echo (isset($_GET['usuario_id']) && $_GET['usuario_id'] == $usuario['id_usuario']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($usuario['nombre_completo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="input-group">
                                <label for="mes">Mes:</label>
                                <select id="mes" name="mes">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $i == $mes ? 'selected' : ''; ?>>
                                            <?php echo $meses[$i]; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="input-group">
                                <label for="año">Año:</label>
                                <select id="año" name="año">
                                    <?php for ($i = 2020; $i <= 2030; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $i == $año ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Generar Reporte</button>
                        </form>
                    </div>
                </div>

                <!-- Acciones -->
                <?php if (($tipo_reporte == 'general' && !empty($datos_reporte)) || ($tipo_reporte == 'individual' && !empty($resumen_usuario))): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <form method="POST" style="display: inline;">
                                    <?php if ($tipo_reporte == 'individual'): ?>
                                        <input type="hidden" name="usuario_id" value="<?php echo $_GET['usuario_id'] ?? ''; ?>">
                                    <?php endif; ?>
                                    <button type="submit" name="generar_pdf" class="btn btn-success">
                                        📄 Descargar PDF
                                    </button>
                                </form>
                                <button onclick="window.print()" class="btn btn-secondary">
                                    🖨️ Imprimir
                                </button>
                                <a href="inicio_admin.php" class="btn btn-primary">
                                    ← Volver al Panel
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Preview del Reporte -->
            <?php if ($tipo_reporte == 'general'): ?>
                <!-- Reporte General -->
                <div class="reporte-preview">
                    <div class="reporte-header">
                        <h1>Sistema de Gestión Hídrica Comunitaria</h1>
                        <h2>Reporte General de Bombeo</h2>
                        <h3><?php echo $meses[$mes] . ' ' . $año; ?></h3>
                        <p>Fecha de generación: <?php echo date('d/m/Y H:i:s'); ?></p>
                    </div>

                    <?php if (!empty($datos_reporte)): ?>
                        <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                            <h4>📊 Resumen General del Mes</h4>
                            <?php
                            $total_sesiones = array_sum(array_column($datos_reporte, 'total_sesiones'));
                            $total_horas_general = array_sum(array_column($datos_reporte, 'total_horas'));
                            $total_litros_general = array_sum(array_column($datos_reporte, 'total_litros'));
                            ?>
                            <p><strong>Total de usuarios activos:</strong> <?php echo count($datos_reporte); ?></p>
                            <p><strong>Total de sesiones de bombeo:</strong> <?php echo $total_sesiones; ?></p>
                            <p><strong>Total de horas bombeadas:</strong> <?php echo number_format($total_horas_general, 2); ?> hrs</p>
                            <p><strong>Total de litros bombeados:</strong> <?php echo number_format($total_litros_general); ?> litros</p>
                        </div>

                        <h4>📋 Detalle por Usuario</h4>
                        <table class="table" style="margin-top: 15px;">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>RFC</th>
                                    <th style="text-align: center;">Sesiones</th>
                                    <th style="text-align: center;">Horas</th>
                                    <th style="text-align: right;">Litros</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos_reporte as $usuario): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['rfc']); ?></td>
                                        <td style="text-align: center;"><?php echo $usuario['total_sesiones']; ?></td>
                                        <td style="text-align: center;"><?php echo number_format($usuario['total_horas'], 2); ?></td>
                                        <td style="text-align: right;"><?php echo number_format($usuario['total_litros']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background-color: #f8f9fa; font-weight: bold;">
                                    <td colspan="2"><strong>TOTALES</strong></td>
                                    <td style="text-align: center;"><strong><?php echo $total_sesiones; ?></strong></td>
                                    <td style="text-align: center;"><strong><?php echo number_format($total_horas_general, 2); ?></strong></td>
                                    <td style="text-align: right;"><strong><?php echo number_format($total_litros_general); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #6c757d; background: #f8f9fa; border-radius: 5px;">
                            <h3>📭 Sin Datos</h3>
                            <p>No hay registros de bombeo para este período.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($tipo_reporte == 'individual' && !empty($resumen_usuario)): ?>
                <!-- Reporte Individual -->
                <div class="reporte-preview">
                    <div class="reporte-header">
                        <h1>Sistema de Gestión Hídrica Comunitaria</h1>
                        <h2>Reporte Individual de Bombeo</h2>
                        <h3><?php echo $meses[$mes] . ' ' . $año; ?></h3>
                    </div>

                    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <h4>👤 Información del Usuario</h4>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($resumen_usuario['nombre_completo']); ?></p>
                        <p><strong>RFC:</strong> <?php echo htmlspecialchars($resumen_usuario['rfc']); ?></p>
                        <p><strong>Fecha de generación:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                    </div>

                    <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <h4>📊 Resumen del Mes</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; text-align: center; margin-top: 15px;">
                            <div style="background: #007bff; color: white; padding: 15px; border-radius: 5px;">
                                <div style="font-size: 1.5em; font-weight: bold;"><?php echo $resumen_usuario['total_sesiones']; ?></div>
                                <div style="font-size: 0.9em;">Sesiones</div>
                            </div>
                            <div style="background: #28a745; color: white; padding: 15px; border-radius: 5px;">
                                <div style="font-size: 1.5em; font-weight: bold;"><?php echo number_format($resumen_usuario['total_horas'], 1); ?></div>
                                <div style="font-size: 0.9em;">Horas</div>
                            </div>
                            <div style="background: #17a2b8; color: white; padding: 15px; border-radius: 5px;">
                                <div style="font-size: 1.5em; font-weight: bold;"><?php echo number_format($resumen_usuario['total_litros']); ?></div>
                                <div style="font-size: 0.9em;">Litros</div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle de bombeos -->
                    <?php if (!empty($datos_bombeo)): ?>
                        <h4>📋 Detalle de Bombeos</h4>
                        <table class="table" style="margin-top: 15px;">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora Inicio</th>
                                    <th>Hora Fin</th>
                                    <th>Horas</th>
                                    <th>Litros</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos_bombeo as $bombeo): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($bombeo['fecha_bombeo'])); ?></td>
                                        <td><?php echo $bombeo['hora_inicio']; ?></td>
                                        <td><?php echo $bombeo['hora_fin']; ?></td>
                                        <td><?php echo number_format($bombeo['horas_bombeadas'], 1); ?></td>
                                        <td><?php echo number_format($bombeo['litros_bombeados']); ?></td>
                                        <td><?php echo htmlspecialchars($bombeo['observaciones'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background-color: #f8f9fa; font-weight: bold;">
                                    <td colspan="3">TOTALES</td>
                                    <td><?php echo number_format($resumen_usuario['total_horas'], 1); ?></td>
                                    <td><?php echo number_format($resumen_usuario['total_litros']); ?></td>
                                    <td>-</td>
                                </tr>
                            </tfoot>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #6c757d; background: #f8f9fa; border-radius: 5px;">
                            <h3>📭 Sin Actividad</h3>
                            <p>No hay registros de bombeo para este usuario en este período.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center" style="padding: 60px;">
                        <h3>📄 Selecciona las opciones del reporte</h3>
                        <p>Configura el tipo de reporte, período y usuario (si aplica) para generar el reporte correspondiente.</p>
                        <?php if ($tipo_reporte == 'individual' && empty($_GET['usuario_id'])): ?>
                            <div class="alert alert-warning">
                                <strong>Nota:</strong> Para reportes individuales, selecciona un usuario específico.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <script>
        function toggleUsuarioSelector() {
            const tipo = document.getElementById('tipo').value;
            const selectorUsuario = document.getElementById('selectorUsuario');
            
            if (tipo === 'individual') {
                selectorUsuario.style.display = 'block';
            } else {
                selectorUsuario.style.display = 'none';
            }
        }
        
        // Función para mostrar/ocultar sidebar en móviles
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>
</html>