<?php
/**
 * Nueva Reserva de Bombeo - Usuario (MODIFICADO)
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

session_start();
require_once '../../config/conexion.php';
require_once '../../config/config.php';
require_once '../../funciones/sesiones.php';
require_once '../../funciones/validaciones.php';

// Verificar acceso
requerir_login();
if (es_admin()) {
    header('Location: ../admin/inicio_admin.php');
    exit();
}

$usuario = obtener_usuario_actual();
$errores = [];
$exito = '';

// Fecha preseleccionada desde el calendario
$fecha_preseleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : '';

// Horarios disponibles por hora individual
$horarios_individuales = [
    '06:00' => '06:00 - 07:00',
    '07:00' => '07:00 - 08:00',
    '08:00' => '08:00 - 09:00',
    '09:00' => '09:00 - 10:00',
    '10:00' => '10:00 - 11:00',
    '11:00' => '11:00 - 12:00',
    '14:00' => '14:00 - 15:00',
    '15:00' => '15:00 - 16:00',
    '16:00' => '16:00 - 17:00',
    '17:00' => '17:00 - 18:00'
];

// Función para validar que las horas sean consecutivas
function validar_horas_consecutivas($horas_seleccionadas) {
    if (count($horas_seleccionadas) <= 1) {
        return true;
    }
    
    // Convertir a formato numérico y ordenar
    $horas_numericas = [];
    foreach ($horas_seleccionadas as $hora) {
        $horas_numericas[] = (int)str_replace(':', '', $hora);
    }
    sort($horas_numericas);
    
    // Verificar consecutividad
    for ($i = 1; $i < count($horas_numericas); $i++) {
        $anterior = $horas_numericas[$i - 1];
        $actual = $horas_numericas[$i];
        
        // Permitir salto de 11:00 a 14:00
        if ($anterior == 1100 && $actual == 1400) {
            continue;
        }
        
        // Las demás deben ser consecutivas (diferencia de 100)
        if ($actual - $anterior != 100) {
            return false;
        }
    }
    
    return true;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'crear_reserva') {
    $fecha = limpiar_entrada($_POST['fecha']);
    $horas_seleccionadas = isset($_POST['horarios']) ? $_POST['horarios'] : [];
    $observaciones = limpiar_entrada($_POST['observaciones']);
    
    // Validaciones básicas
    if (empty($fecha)) {
        $errores[] = 'La fecha es requerida';
    } elseif (!validar_fecha($fecha)) {
        $errores[] = 'La fecha no tiene un formato válido';
    } elseif (!validar_fecha_futura($fecha)) {
        $errores[] = 'No se pueden hacer reservas en fechas pasadas';
    }
    
    if (empty($horas_seleccionadas)) {
        $errores[] = 'Debes seleccionar al menos una hora';
    } else {
        // Validar que las horas sean válidas
        foreach ($horas_seleccionadas as $hora) {
            if (!array_key_exists($hora, $horarios_individuales)) {
                $errores[] = 'Una o más horas seleccionadas no son válidas';
                break;
            }
        }
        
        // Validar que sean consecutivas
        if (!validar_horas_consecutivas($horas_seleccionadas)) {
            $errores[] = 'Las horas seleccionadas deben ser consecutivas';
        }
    }
    
    // Verificar límite de solicitudes pendientes (no horas)
    if (empty($errores)) {
        $query_pendientes = "SELECT COUNT(*) as total FROM reserva 
                            WHERE id_usuario = ? AND estado = 'pendiente'";
        $stmt_pendientes = $conexion->prepare($query_pendientes);
        $stmt_pendientes->bind_param("i", $usuario['id']);
        $stmt_pendientes->execute();
        $pendientes = $stmt_pendientes->get_result()->fetch_assoc();
        
        if ($pendientes['total'] >= 3) {
            $errores[] = 'No puedes tener más de 3 solicitudes pendientes al mismo tiempo';
        }
    }
    
    // Verificar disponibilidad de todas las horas
    if (empty($errores)) {
        foreach ($horas_seleccionadas as $hora_inicio) {
            $hora_fin = date('H:i:s', strtotime($hora_inicio . ':00 +1 hour'));
            $hora_inicio_completa = $hora_inicio . ':00';
            
            $query_disponibilidad = "SELECT COUNT(*) as total FROM reserva 
                                    WHERE fecha = ? AND hora_inicio <= ? AND hora_fin > ? 
                                    AND estado IN ('pendiente', 'aprobada')";
            $stmt_disponibilidad = $conexion->prepare($query_disponibilidad);
            $stmt_disponibilidad->bind_param("sss", $fecha, $hora_inicio_completa, $hora_inicio_completa);
            $stmt_disponibilidad->execute();
            $disponibilidad = $stmt_disponibilidad->get_result()->fetch_assoc();
            
            if ($disponibilidad['total'] > 0) {
                $errores[] = "El horario {$hora_inicio}:00 ya está ocupado para esa fecha";
            }
        }
    }
    
    // Crear una sola reserva con el rango completo
    if (empty($errores)) {
        // Ordenar horas y calcular rango
        sort($horas_seleccionadas);
        $hora_inicio_rango = $horas_seleccionadas[0] . ':00';
        $ultima_hora = end($horas_seleccionadas);
        $hora_fin_rango = date('H:i:s', strtotime($ultima_hora . ':00 +1 hour'));
        
        // Insertar horario disponible
        $query_horario = "INSERT IGNORE INTO horarios_disponibles (horario_inicio, horario_fin, fecha, litros_por_hora) 
                         VALUES (?, ?, ?, ?)";
        $stmt_horario = $conexion->prepare($query_horario);
        $litros_por_hora = LITROS_POR_HORA;
        $stmt_horario->bind_param("sssi", $hora_inicio_rango, $hora_fin_rango, $fecha, $litros_por_hora);
        $stmt_horario->execute();
        
        // Obtener ID del horario
        $query_id_horario = "SELECT id_horario FROM horarios_disponibles 
                            WHERE horario_inicio = ? AND horario_fin = ? AND fecha = ?";
        $stmt_id_horario = $conexion->prepare($query_id_horario);
        $stmt_id_horario->bind_param("sss", $hora_inicio_rango, $hora_fin_rango, $fecha);
        $stmt_id_horario->execute();
        $resultado_horario = $stmt_id_horario->get_result()->fetch_assoc();
        $id_horario = $resultado_horario['id_horario'];
        
        // Crear la reserva
        $horas_total = count($horas_seleccionadas);
        $observaciones_completas = $observaciones . " [Reserva de {$horas_total} hora(s) consecutiva(s)]";
        $query_reserva = "INSERT INTO reserva (id_usuario, id_horario, fecha, hora_inicio, hora_fin, observaciones) 
                         VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_reserva = $conexion->prepare($query_reserva);
        $stmt_reserva->bind_param("iissss", $usuario['id'], $id_horario, $fecha, $hora_inicio_rango, $hora_fin_rango, $observaciones_completas);
        
        if ($stmt_reserva->execute()) {
            $exito = "Reserva creada exitosamente para {$horas_total} hora(s) consecutiva(s) ({$hora_inicio_rango} - {$hora_fin_rango}). Está pendiente de aprobación.";
            $fecha_preseleccionada = '';
            $observaciones = '';
        } else {
            $errores[] = 'Error al crear la reserva. Intenta nuevamente.';
        }
    }
}

$titulo_pagina = "Nueva Reserva";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> - Sistema GHC</title>
    <link rel="stylesheet" href="../../css/estilos.css">
    <style>
        .horarios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .horario-item {
            position: relative;
        }
        
        .horario-checkbox {
            display: none;
        }
        
        .horario-label {
            display: block;
            padding: 12px 15px;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .horario-label:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }
        
        .horario-checkbox:checked + .horario-label {
            background: #007bff;
            color: white;
            border-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }
        
        .horario-ocupado {
            background: #f8d7da !important;
            color: #721c24 !important;
            border-color: #f5c6cb !important;
            cursor: not-allowed !important;
        }
        
        .seleccion-info {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .litros-calculados {
            font-weight: bold;
            color: #007bff;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
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
    <nav class="sidebar">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="inicio.php" class="nav-link">📊 Panel Principal</a>
            </li>
            <li class="nav-item">
                <a href="calendario.php" class="nav-link">📅 Calendario de Reservas</a>
            </li>
            <li class="nav-item">
                <a href="nueva_reserva.php" class="nav-link active">➕ Nueva Reserva</a>
            </li>
            <li class="nav-item">
                <a href="mis_reservas.php" class="nav-link">📋 Mis Reservas</a>
            </li>
            <li class="nav-item">
                <a href="mi_reporte.php" class="nav-link">📄 Mi Reporte</a>
            </li>
        </ul>
    </nav>

    <!-- Contenido Principal -->
    <main class="main-content">
        <div class="container">
            <h1>➕ Nueva Reserva de Bombeo</h1>
            <p class="mb-4">Solicita una reserva para bombeo de agua del pozo comunitario. Las horas seleccionadas deben ser consecutivas.</p>

            <?php if (!empty($errores)): ?>
                <div class="alert alert-error">
                    <strong>Errores encontrados:</strong>
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($exito)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($exito); ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">
                
                <!-- Formulario -->
                <div class="card">
                    <div class="card-header">
                        📝 Datos de la Reserva
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formReserva">
                            <input type="hidden" name="accion" value="crear_reserva">
                            
                            <div class="input-group">
                                <label for="fecha">Fecha de Bombeo: *</label>
                                <input 
                                    type="date" 
                                    id="fecha" 
                                    name="fecha" 
                                    required 
                                    min="<?php echo date('Y-m-d'); ?>"
                                    value="<?php echo htmlspecialchars($fecha_preseleccionada); ?>"
                                    onchange="verificarDisponibilidad()"
                                >
                                <small>La fecha debe ser posterior a hoy</small>
                            </div>
                            
                            <div class="input-group">
                                <label>Horarios de Bombeo: *</label>
                                <small>Selecciona horas consecutivas (cada hora = ~52,000 litros)</small>
                                
                                <div class="horarios-grid" id="horariosGrid">
                                    <?php foreach ($horarios_individuales as $hora => $descripcion): ?>
                                        <div class="horario-item">
                                            <input 
                                                type="checkbox" 
                                                id="horario_<?php echo $hora; ?>" 
                                                name="horarios[]" 
                                                value="<?php echo $hora; ?>"
                                                class="horario-checkbox"
                                                onchange="actualizarSeleccion()"
                                            >
                                            <label for="horario_<?php echo $hora; ?>" class="horario-label">
                                                <?php echo $descripcion; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div id="seleccionInfo" class="seleccion-info" style="display: none;">
                                    <div>Horas seleccionadas: <span id="horasSeleccionadas">0</span></div>
                                    <div>Litros aproximados: <span id="litrosCalculados" class="litros-calculados">0</span></div>
                                </div>
                            </div>
                            
                            <div id="disponibilidad-info" style="display: none; margin-bottom: 15px;"></div>
                            
                            <div class="input-group">
                                <label for="observaciones">Observaciones:</label>
                                <textarea 
                                    id="observaciones" 
                                    name="observaciones" 
                                    rows="3" 
                                    placeholder="Información adicional sobre la reserva (opcional)"
                                ><?php echo isset($observaciones) ? htmlspecialchars($observaciones) : ''; ?></textarea>
                            </div>
                            
                            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                <a href="calendario.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary" id="btnEnviar" disabled>
                                    Crear Reserva
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Información lateral -->
                <div>
                    <div class="card">
                        <div class="card-header">
                            ℹ️ Información Importante
                        </div>
                        <div class="card-body">
                            <h4>Capacidad del Pozo:</h4>
                            <p>Aproximadamente <strong>52,000 litros por hora</strong> de bombeo.</p>
                            
                            <h4>Horarios Disponibles:</h4>
                            <ul style="padding-left: 15px;">
                                <li><strong>Matutino:</strong> 06:00 - 12:00 hrs</li>
                                <li><strong>Vespertino:</strong> 14:00 - 18:00 hrs</li>
                                <li><strong>Pausa:</strong> 12:00 - 14:00 hrs (mantenimiento)</li>
                            </ul>
                            
                            <h4>Proceso:</h4>
                            <ol style="padding-left: 15px;">
                                <li>Selecciona fecha y horas consecutivas</li>
                                <li>Envías tu solicitud</li>
                                <li>El administrador la revisa</li>
                                <li>Recibes aprobación</li>
                                <li>El día indicado se realiza el bombeo</li>
                            </ol>
                            
                            <h4>Importante:</h4>
                            <ul style="padding-left: 15px; color: #dc3545;">
                                <li>Máximo 3 solicitudes pendientes</li>
                                <li>Las horas deben ser consecutivas</li>
                                <li>Reservar con 24h de anticipación</li>
                                <li>Cancelar si no se va a usar</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            📊 Tu Estado Actual
                        </div>
                        <div class="card-body">
                            <?php
                            // Obtener estadísticas del usuario
                            $query_stats = "SELECT 
                                (SELECT COUNT(*) FROM reserva WHERE id_usuario = ? AND estado = 'pendiente') as pendientes,
                                (SELECT COUNT(*) FROM reserva WHERE id_usuario = ? AND MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())) as este_mes";
                            $stmt_stats = $conexion->prepare($query_stats);
                            $stmt_stats->bind_param("ii", $usuario['id'], $usuario['id']);
                            $stmt_stats->execute();
                            $stats = $stmt_stats->get_result()->fetch_assoc();
                            ?>
                            
                            <p><strong>Solicitudes pendientes:</strong> <?php echo $stats['pendientes']; ?>/3</p>
                            <p><strong>Reservas este mes:</strong> <?php echo $stats['este_mes']; ?></p>
                            
                            <?php if ($stats['pendientes'] >= 3): ?>
                                <div class="alert alert-warning" style="margin-top: 10px; padding: 10px;">
                                    <small>⚠️ Has alcanzado el límite de solicitudes pendientes. Espera a que se aprueben o cancela alguna.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function verificarDisponibilidad() {
            const fecha = document.getElementById('fecha').value;
            
            if (!fecha) {
                // Habilitar todos los horarios
                document.querySelectorAll('.horario-label').forEach(label => {
                    label.classList.remove('horario-ocupado');
                });
                return;
            }
            
            // Habilitar todos los horarios (sin simulación)
            document.querySelectorAll('.horario-checkbox').forEach(checkbox => {
                const label = checkbox.nextElementSibling;
                label.classList.remove('horario-ocupado');
                checkbox.disabled = false;
            });
            
            actualizarSeleccion();
        }
        
        function actualizarSeleccion() {
            const checkboxes = document.querySelectorAll('.horario-checkbox:checked');
            const info = document.getElementById('seleccionInfo');
            const btnEnviar = document.getElementById('btnEnviar');
            const horasSpan = document.getElementById('horasSeleccionadas');
            const litrosSpan = document.getElementById('litrosCalculados');
            
            const horasSeleccionadas = checkboxes.length;
            const litrosCalculados = horasSeleccionadas * 52000;
            
            if (horasSeleccionadas > 0) {
                info.style.display = 'block';
                btnEnviar.disabled = false;
                horasSpan.textContent = horasSeleccionadas;
                litrosSpan.textContent = litrosCalculados.toLocaleString();
            } else {
                info.style.display = 'none';
                btnEnviar.disabled = true;
            }
        }
        
        // Inicializar
        actualizarSeleccion();
        
        // Función para mostrar/ocultar sidebar en móviles
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>
</html>