<?php
/**
 * Registro de Bombeo - Administrador
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

session_start();
require_once '../../config/conexion.php';
require_once '../../config/config.php';
require_once '../../funciones/sesiones.php';
require_once '../../funciones/validaciones.php';

// Verificar que sea administrador
requerir_admin();

$usuario_admin = obtener_usuario_actual();
$mensaje = '';
$tipo_mensaje = '';

// Procesar registro de bombeo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'registrar_bombeo') {
    $id_reserva = (int)$_POST['id_reserva'];
    $hora_inicio_real = $_POST['hora_inicio_real'];
    $hora_fin_real = $_POST['hora_fin_real'];
    $observaciones_bombeo = limpiar_entrada($_POST['observaciones_bombeo']);
    
    // Validaciones
    if (!validar_hora($hora_inicio_real) || !validar_hora($hora_fin_real)) {
        $mensaje = "Formato de hora inválido.";
        $tipo_mensaje = "error";
    } elseif (strtotime($hora_fin_real) <= strtotime($hora_inicio_real)) {
        $mensaje = "La hora de fin debe ser posterior a la hora de inicio.";
        $tipo_mensaje = "error";
    } else {
        // Obtener datos de la reserva
        $query_reserva = "SELECT * FROM reserva WHERE id_reserva = ? AND estado = 'aprobada'";
        $stmt_reserva = $conexion->prepare($query_reserva);
        $stmt_reserva->bind_param("i", $id_reserva);
        $stmt_reserva->execute();
        $reserva = $stmt_reserva->get_result()->fetch_assoc();
        
        if ($reserva) {
            // Calcular horas y litros bombeados
            $horas_bombeadas = (strtotime($hora_fin_real) - strtotime($hora_inicio_real)) / 3600;
            $litros_bombeados = round($horas_bombeadas * LITROS_POR_HORA);
            
            // Iniciar transacción
            $conexion->begin_transaction();
            
            try {
                // Insertar registro de bombeo
                $query_registro = "INSERT INTO registro_bombeo 
                    (id_reserva, id_usuario, id_administrador, fecha_bombeo, hora_inicio, hora_fin, 
                     horas_bombeadas, litros_bombeados, observaciones) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt_registro = $conexion->prepare($query_registro);
                $stmt_registro->bind_param("iiisssids", 
                    $id_reserva, 
                    $reserva['id_usuario'], 
                    $usuario_admin['id'], 
                    $reserva['fecha'], 
                    $hora_inicio_real, 
                    $hora_fin_real, 
                    $horas_bombeadas, 
                    $litros_bombeados, 
                    $observaciones_bombeo
                );
                
                if (!$stmt_registro->execute()) {
                    throw new Exception("Error al insertar registro de bombeo");
                }
                
                // Actualizar estado de la reserva
                $query_actualizar = "UPDATE reserva SET estado = 'ejecutada', litros_bombeados = ? WHERE id_reserva = ?";
                $stmt_actualizar = $conexion->prepare($query_actualizar);
                $stmt_actualizar->bind_param("ii", $litros_bombeados, $id_reserva);
                
                if (!$stmt_actualizar->execute()) {
                    throw new Exception("Error al actualizar estado de reserva");
                }
                
                // Confirmar transacción
                $conexion->commit();
                
                $mensaje = "Bombeo registrado exitosamente. Horas: " . number_format($horas_bombeadas, 2) . ", Litros: " . number_format($litros_bombeados);
                $tipo_mensaje = "success";
                
            } catch (Exception $e) {
                $conexion->rollback();
                $mensaje = "Error al registrar el bombeo: " . $e->getMessage();
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Reserva no encontrada o no está aprobada.";
            $tipo_mensaje = "error";
        }
    }
}

// Obtener reservas aprobadas para hoy y días recientes
$fecha_filtro = $_GET['fecha'] ?? date('Y-m-d');

$query_reservas = "SELECT r.*, u.nombre_completo, u.rfc, u.telefono,
    (SELECT COUNT(*) FROM registro_bombeo WHERE id_reserva = r.id_reserva) as ya_registrado
    FROM reserva r 
    INNER JOIN usuarios u ON r.id_usuario = u.id_usuario 
    WHERE r.fecha = ? AND r.estado IN ('aprobada', 'ejecutada')
    ORDER BY r.hora_inicio ASC";

$stmt_reservas = $conexion->prepare($query_reservas);
$stmt_reservas->bind_param("s", $fecha_filtro);
$stmt_reservas->execute();
$reservas_del_dia = $stmt_reservas->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener últimos registros de bombeo
$query_ultimos = "SELECT rb.*, r.fecha, r.hora_inicio as hora_programada, r.hora_fin as hora_programada_fin, 
    u.nombre_completo, u.rfc
    FROM registro_bombeo rb 
    INNER JOIN reserva r ON rb.id_reserva = r.id_reserva
    INNER JOIN usuarios u ON rb.id_usuario = u.id_usuario
    ORDER BY rb.fecha_registro DESC 
    LIMIT 10";
$ultimos_registros = $conexion->query($query_ultimos)->fetch_all(MYSQLI_ASSOC);

$titulo_pagina = "Registro de Bombeo";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> - Sistema GHC</title>
    <link rel="stylesheet" href="../../css/estilos.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
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
    <nav class="sidebar">
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
                <a href="registro_bombeo.php" class="nav-link active">⚙️ Registro de Bombeo</a>
            </li>
            <li class="nav-item">
                <a href="reportes.php" class="nav-link">📄 Reportes</a>
            </li>
        </ul>
    </nav>

    <!-- Contenido Principal -->
    <main class="main-content">
        <div class="container">
            <h1>⚙️ Registro de Bombeo</h1>
            <p class="mb-4">Registra los bombeos realizados y controla la operación del pozo.</p>

            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <!-- Selector de fecha -->
            <div class="card mb-4">
                <div class="card-header">
                    📅 Seleccionar Fecha
                </div>
                <div class="card-body">
                    <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                        <div class="input-group" style="flex: 1; min-width: 200px;">
                            <label for="fecha">Fecha:</label>
                            <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($fecha_filtro); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Ver Reservas</button>
                        <a href="?fecha=<?php echo date('Y-m-d'); ?>" class="btn btn-secondary">Hoy</a>
                    </form>
                </div>
            </div>

            <!-- Información del día -->
            <div class="card mb-4">
                <div class="card-header">
                    ℹ️ Información del Día: <?php echo date('d/m/Y', strtotime($fecha_filtro)); ?>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; text-align: center;">
                        <div>
                            <h3 style="color: #007bff; margin: 0;"><?php echo count($reservas_del_dia); ?></h3>
                            <p>Reservas Programadas</p>
                        </div>
                        <div>
                            <h3 style="color: #28a745; margin: 0;">
                                <?php echo count(array_filter($reservas_del_dia, function($r) { return $r['ya_registrado'] > 0; })); ?>
                            </h3>
                            <p>Bombeos Registrados</p>
                        </div>
                        <div>
                            <h3 style="color: #ffc107; margin: 0;">
                                <?php echo count(array_filter($reservas_del_dia, function($r) { return $r['ya_registrado'] == 0 && $r['estado'] == 'aprobada'; })); ?>
                            </h3>
                            <p>Pendientes</p>
                        </div>
                        <div>
                            <?php 
                            $total_horas_programadas = 0;
                            foreach($reservas_del_dia as $r) {
                                $total_horas_programadas += (strtotime($r['hora_fin']) - strtotime($r['hora_inicio'])) / 3600;
                            }
                            ?>
                            <h3 style="color: #17a2b8; margin: 0;"><?php echo number_format($total_horas_programadas, 1); ?></h3>
                            <p>Horas Programadas</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reservas del día -->
            <div class="card mb-4">
                <div class="card-header">
                    🚰 Reservas para <?php echo date('d/m/Y', strtotime($fecha_filtro)); ?>
                </div>
                <div class="card-body">
                    <?php if (count($reservas_del_dia) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Horario Programado</th>
                                        <th>Estado</th>
                                        <th>Litros Estimados</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservas_del_dia as $reserva): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($reserva['nombre_completo']); ?></strong><br>
                                                <small style="color: #666;">
                                                    RFC: <?php echo htmlspecialchars($reserva['rfc']); ?><br>
                                                    Tel: <?php echo htmlspecialchars($reserva['telefono'] ?? 'N/A'); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php echo date('H:i', strtotime($reserva['hora_inicio'])); ?> - 
                                                <?php echo date('H:i', strtotime($reserva['hora_fin'])); ?>
                                                <br>
                                                <small style="color: #666;">
                                                    <?php 
                                                    $horas = (strtotime($reserva['hora_fin']) - strtotime($reserva['hora_inicio'])) / 3600;
                                                    echo $horas . " hora(s)";
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="estado-<?php echo $reserva['estado']; ?>">
                                                    <?php echo ucfirst($reserva['estado']); ?>
                                                </span>
                                                <?php if ($reserva['ya_registrado'] > 0): ?>
                                                    <br><small style="color: #28a745;">✓ Registrado</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $horas = (strtotime($reserva['hora_fin']) - strtotime($reserva['hora_inicio'])) / 3600;
                                                $litros_estimados = $horas * LITROS_POR_HORA;
                                                echo number_format($litros_estimados);
                                                ?>
                                                <br>
                                                <small style="color: #666;">litros aprox.</small>
                                            </td>
                                            <td>
                                                <?php if ($reserva['ya_registrado'] == 0 && $reserva['estado'] == 'aprobada'): ?>
                                                    <button onclick="abrirModalRegistro(<?php echo $reserva['id_reserva']; ?>, '<?php echo addslashes($reserva['nombre_completo']); ?>', '<?php echo $reserva['hora_inicio']; ?>', '<?php echo $reserva['hora_fin']; ?>')" 
                                                            class="btn btn-success btn-small">
                                                        ⚙️ Registrar Bombeo
                                                    </button>
                                                <?php elseif ($reserva['ya_registrado'] > 0): ?>
                                                    <small style="color: #28a745;">✓ Ya registrado</small>
                                                <?php else: ?>
                                                    <small style="color: #999;">No disponible</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 40px; color: #6c757d;">
                            <h3>📅 No hay reservas</h3>
                            <p>No hay reservas programadas para esta fecha.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Últimos registros -->
            <div class="card">
                <div class="card-header">
                    📋 Últimos Registros de Bombeo
                </div>
                <div class="card-body">
                    <?php if (count($ultimos_registros) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Fecha</th>
                                        <th>Horario Real</th>
                                        <th>Resultado</th>
                                        <th>Observaciones</th>
                                        <th>Registro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimos_registros as $registro): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($registro['nombre_completo']); ?></strong><br>
                                                <small style="color: #666;">RFC: <?php echo htmlspecialchars($registro['rfc']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($registro['fecha'])); ?><br>
                                                <small style="color: #666;">
                                                    Prog: <?php echo date('H:i', strtotime($registro['hora_programada'])); ?>-<?php echo date('H:i', strtotime($registro['hora_programada_fin'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php echo $registro['hora_inicio']; ?> - <?php echo $registro['hora_fin']; ?><br>
                                                <small style="color: #666;"><?php echo number_format($registro['horas_bombeadas'], 2); ?> hrs</small>
                                            </td>
                                            <td>
                                                <strong style="color: #28a745;"><?php echo number_format($registro['litros_bombeados']); ?> L</strong><br>
                                                <small style="color: #666;">~<?php echo number_format($registro['litros_bombeados'] / $registro['horas_bombeadas']); ?> L/h</small>
                                            </td>
                                            <td>
                                                <?php if (!empty($registro['observaciones'])): ?>
                                                    <small><?php echo htmlspecialchars($registro['observaciones']); ?></small>
                                                <?php else: ?>
                                                    <small style="color: #999;">Sin observaciones</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small style="color: #666;">
                                                    <?php echo date('d/m H:i', strtotime($registro['fecha_registro'])); ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 20px; color: #6c757d;">
                            <p>No hay registros de bombeo aún.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal para registrar bombeo -->
    <div id="modalRegistro" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%;">
            <h3>⚙️ Registrar Bombeo Ejecutado</h3>
            <p>Usuario: <strong id="nombreUsuarioRegistro"></strong></p>
            <p>Horario programado: <strong id="horarioProgramado"></strong></p>
            
            <form method="POST" id="formRegistro">
                <input type="hidden" name="accion" value="registrar_bombeo">
                <input type="hidden" name="id_reserva" id="idReservaRegistro">
                
                <div class="input-group">
                    <label for="hora_inicio_real">Hora de Inicio Real: *</label>
                    <input type="time" id="hora_inicio_real" name="hora_inicio_real" required>
                </div>
                
                <div class="input-group">
                    <label for="hora_fin_real">Hora de Fin Real: *</label>
                    <input type="time" id="hora_fin_real" name="hora_fin_real" required>
                </div>
                
                <div class="input-group">
                    <label for="observaciones_bombeo">Observaciones del Bombeo:</label>
                    <textarea id="observaciones_bombeo" name="observaciones_bombeo" rows="3" placeholder="Estado del pozo, incidencias, etc."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="cerrarModalRegistro()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-success">Registrar Bombeo</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalRegistro(idReserva, nombreUsuario, horaInicio, horaFin) {
            document.getElementById('idReservaRegistro').value = idReserva;
            document.getElementById('nombreUsuarioRegistro').textContent = nombreUsuario;
            document.getElementById('horarioProgramado').textContent = horaInicio + ' - ' + horaFin;
            
            // Pre-llenar con horarios programados
            document.getElementById('hora_inicio_real').value = horaInicio;
            document.getElementById('hora_fin_real').value = horaFin;
            
            document.getElementById('modalRegistro').style.display = 'block';
        }
        
        function cerrarModalRegistro() {
            document.getElementById('modalRegistro').style.display = 'none';
            document.getElementById('formRegistro').reset();
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalRegistro');
            if (event.target == modal) {
                modal.style.display = 'none';
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