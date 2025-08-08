<?php
/**
 * Mi Reporte Individual - Usuario
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

session_start();
require_once '../../config/conexion.php';
require_once '../../config/config.php';
require_once '../../funciones/sesiones.php';

// Verificar acceso
requerir_login();
if (es_admin()) {
    header('Location: ../admin/inicio_admin.php');
    exit();
}

$usuario = obtener_usuario_actual();
$mensaje = '';

// Parámetros del reporte
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$año = isset($_GET['año']) ? (int)$_GET['año'] : date('Y');

// Validar parámetros
if ($mes < 1 || $mes > 12) $mes = date('n');
if ($año < 2020 || $año > 2030) $año = date('Y');

// Nombres de meses
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Función local para obtener datos de bombeo del usuario
function obtener_datos_bombeo_usuario_local($usuario_id, $mes, $año, $conexion) {
    $query = "SELECT rb.*, u.nombre_completo, u.rfc 
              FROM registro_bombeo rb 
              INNER JOIN usuarios u ON rb.id_usuario = u.id_usuario 
              WHERE rb.id_usuario = ? 
              AND MONTH(rb.fecha_bombeo) = ? 
              AND YEAR(rb.fecha_bombeo) = ?
              ORDER BY rb.fecha_bombeo ASC";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("iii", $usuario_id, $mes, $año);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Función local para obtener resumen del usuario
function obtener_resumen_usuario_local($usuario_id, $mes, $año, $conexion) {
    $query = "SELECT 
                COUNT(*) as total_sesiones,
                SUM(rb.horas_bombeadas) as total_horas,
                SUM(rb.litros_bombeados) as total_litros,
                u.nombre_completo,
                u.rfc
              FROM registro_bombeo rb 
              INNER JOIN usuarios u ON rb.id_usuario = u.id_usuario 
              WHERE rb.id_usuario = ? 
              AND MONTH(rb.fecha_bombeo) = ? 
              AND YEAR(rb.fecha_bombeo) = ?";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("iii", $usuario_id, $mes, $año);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
    
    // Si no hay datos, devolver valores en cero
    if ($resultado['total_sesiones'] == 0) {
        $query_usuario = "SELECT nombre_completo, rfc FROM usuarios WHERE id_usuario = ?";
        $stmt_usuario = $conexion->prepare($query_usuario);
        $stmt_usuario->bind_param("i", $usuario_id);
        $stmt_usuario->execute();
        $usuario_data = $stmt_usuario->get_result()->fetch_assoc();
        
        $resultado = [
            'total_sesiones' => 0,
            'total_horas' => 0,
            'total_litros' => 0,
            'nombre_completo' => $usuario_data['nombre_completo'],
            'rfc' => $usuario_data['rfc']
        ];
    }
    
    return $resultado;
}

// Obtener datos del reporte
$datos_bombeo = obtener_datos_bombeo_usuario_local($usuario['id'], $mes, $año, $conexion);
$resumen = obtener_resumen_usuario_local($usuario['id'], $mes, $año, $conexion);

// Generar PDF si se solicita
if (isset($_POST['generar_pdf'])) {
    $mensaje = "Función de PDF en desarrollo. Los datos se muestran en pantalla.";
}

$titulo_pagina = "Mi Reporte Individual";
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
        
        .info-usuario {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .resumen-mes {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .estadistica {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 5px;
            text-align: center;
            min-width: 120px;
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
                <h1>Sistema GHC</h1>
            </div>
            <div class="user-info">
                <span>👤 <?php echo htmlspecialchars($usuario['nombre']); ?></span>
                <a href="../../logout.php" class="btn btn-secondary btn-small">Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <nav class="sidebar no-print">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="inicio.php" class="nav-link">📊 Panel Principal</a>
            </li>
            <li class="nav-item">
                <a href="calendario.php" class="nav-link">📅 Calendario de Reservas</a>
            </li>
            <li class="nav-item">
                <a href="nueva_reserva.php" class="nav-link">➕ Nueva Reserva</a>
            </li>
            <li class="nav-item">
                <a href="mis_reservas.php" class="nav-link">📋 Mis Reservas</a>
            </li>
            <li class="nav-item">
                <a href="mi_reporte.php" class="nav-link active">📄 Mi Reporte</a>
            </li>
        </ul>
    </nav>

    <!-- Contenido Principal -->
    <main class="main-content">
        <div class="container">
            
            <!-- Controles (solo visible en pantalla) -->
            <div class="no-print">
                <h1>📄 Mi Reporte Individual</h1>
                <p class="mb-4">Consulta y descarga tu reporte de bombeo mensual.</p>

                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>

                <!-- Selector de mes/año -->
                <div class="card mb-4">
                    <div class="card-header">
                        📅 Seleccionar Período
                    </div>
                    <div class="card-body">
                        <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                            <div class="input-group" style="flex: 1; min-width: 150px;">
                                <label for="mes">Mes:</label>
                                <select id="mes" name="mes">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $i == $mes ? 'selected' : ''; ?>>
                                            <?php echo $meses[$i]; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="input-group" style="flex: 1; min-width: 150px;">
                                <label for="año">Año:</label>
                                <select id="año" name="año">
                                    <?php for ($i = 2020; $i <= 2030; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $i == $año ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Ver Reporte</button>
                        </form>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="generar_pdf" class="btn btn-success">
                                    📄 Descargar PDF
                                </button>
                            </form>
                            <button onclick="window.print()" class="btn btn-secondary">
                                🖨️ Imprimir
                            </button>
                            <a href="inicio.php" class="btn btn-primary">
                                ← Volver al Panel
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview del Reporte -->
            <div class="reporte-preview">
                <div class="reporte-header">
                    <h1>Sistema de Gestión Hídrica Comunitaria</h1>
                    <h2>Reporte Individual de Bombeo</h2>
                    <h3><?php echo $meses[$mes] . ' ' . $año; ?></h3>
                </div>

                <div class="info-usuario">
                    <h4>👤 Información del Usuario</h4>
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($resumen['nombre_completo']); ?></p>
                    <p><strong>RFC:</strong> <?php echo htmlspecialchars($resumen['rfc']); ?></p>
                    <p><strong>Fecha de generación:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                </div>

                <div class="resumen-mes">
                    <h4>📊 Resumen del Mes</h4>
                    <div style="text-align: center; margin-top: 15px;">
                        <div class="estadistica">
                            <div style="font-size: 1.5em; font-weight: bold;"><?php echo $resumen['total_sesiones']; ?></div>
                            <div style="font-size: 0.9em;">Sesiones</div>
                        </div>
                        <div class="estadistica">
                            <div style="font-size: 1.5em; font-weight: bold;"><?php echo number_format($resumen['total_horas'], 1); ?></div>
                            <div style="font-size: 0.9em;">Horas</div>
                        </div>
                        <div class="estadistica">
                            <div style="font-size: 1.5em; font-weight: bold;"><?php echo number_format($resumen['total_litros']); ?></div>
                            <div style="font-size: 0.9em;">Litros</div>
                        </div>
                    </div>
                </div>

                <!-- Detalle de bombeos -->
                <?php if (count($datos_bombeo) > 0): ?>
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
                                <td><?php echo number_format($resumen['total_horas'], 1); ?></td>
                                <td><?php echo number_format($resumen['total_litros']); ?></td>
                                <td>-</td>
                            </tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #6c757d; background: #f8f9fa; border-radius: 5px;">
                        <h3>📭 Sin Actividad</h3>
                        <p>No hay registros de bombeo para este período.</p>
                        <p>Si realizaste bombeos y no aparecen aquí, contacta al administrador.</p>
                    </div>
                <?php endif; ?>

                <!-- Información adicional -->
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 0.9em; color: #666;">
                    <p><strong>Notas importantes:</strong></p>
                    <ul>
                        <li>Este reporte muestra únicamente los bombeos que fueron ejecutados y registrados por el administrador.</li>
                        <li>La capacidad promedio del pozo es de <?php echo number_format(LITROS_POR_HORA); ?> litros por hora.</li>
                        <li>Los datos mostrados son aproximados y pueden variar según las condiciones del pozo.</li>
                        <li>Para consultas o aclaraciones, contacta al administrador del sistema.</li>
                    </ul>
                </div>

                <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 0.8em; color: #999;">
                    <p>Sistema de Gestión Hídrica Comunitaria - Cañada de Flores, Hidalgo</p>
                    <p>Generado el <?php echo date('d/m/Y H:i:s'); ?></p>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Función para mostrar/ocultar sidebar en móviles
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>
</html>