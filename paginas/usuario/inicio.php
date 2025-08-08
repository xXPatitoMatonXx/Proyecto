<?php
/**
 * Dashboard del Usuario
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

session_start();
require_once '../../config/conexion.php';
require_once '../../config/config.php';
require_once '../../funciones/sesiones.php';

// Verificar que esté logueado y sea usuario regular
requerir_login();
if (es_admin()) {
    header('Location: ../admin/inicio_admin.php');
    exit();
}

$usuario = obtener_usuario_actual();

// Obtener estadísticas del usuario
$mes_actual = date('n');
$año_actual = date('Y');

// Reservas pendientes
$query_pendientes = "SELECT COUNT(*) as total FROM reserva WHERE id_usuario = ? AND estado = 'pendiente'";
$stmt_pendientes = $conexion->prepare($query_pendientes);
$stmt_pendientes->bind_param("i", $usuario['id']);
$stmt_pendientes->execute();
$reservas_pendientes = $stmt_pendientes->get_result()->fetch_assoc()['total'];

// Reservas del mes actual
$query_mes = "SELECT COUNT(*) as total FROM reserva WHERE id_usuario = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ?";
$stmt_mes = $conexion->prepare($query_mes);
$stmt_mes->bind_param("iii", $usuario['id'], $mes_actual, $año_actual);
$stmt_mes->execute();
$reservas_mes = $stmt_mes->get_result()->fetch_assoc()['total'];

// Horas bombeadas este mes
$query_horas = "SELECT SUM(horas_bombeadas) as total_horas, SUM(litros_bombeados) as total_litros 
                FROM registro_bombeo 
                WHERE id_usuario = ? AND MONTH(fecha_bombeo) = ? AND YEAR(fecha_bombeo) = ?";
$stmt_horas = $conexion->prepare($query_horas);
$stmt_horas->bind_param("iii", $usuario['id'], $mes_actual, $año_actual);
$stmt_horas->execute();
$datos_mes = $stmt_horas->get_result()->fetch_assoc();
$horas_mes = $datos_mes['total_horas'] ?? 0;
$litros_mes = $datos_mes['total_litros'] ?? 0;

// Próximas reservas
$query_proximas = "SELECT r.*, ha.horario_inicio, ha.horario_fin 
                   FROM reserva r 
                   LEFT JOIN horarios_disponibles ha ON r.id_horario = ha.id_horario
                   WHERE r.id_usuario = ? AND r.fecha >= CURDATE() AND r.estado IN ('pendiente', 'aprobada')
                   ORDER BY r.fecha ASC, r.hora_inicio ASC 
                   LIMIT 5";
$stmt_proximas = $conexion->prepare($query_proximas);
$stmt_proximas->bind_param("i", $usuario['id']);
$stmt_proximas->execute();
$proximas_reservas = $stmt_proximas->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Usuario - Sistema GHC</title>
    <link rel="stylesheet" href="../../css/estilos.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>Sistema GHC</h1>
            </div>
            <div class="user-info">
                <span>Bienvenido, <?php echo htmlspecialchars($usuario['nombre']); ?></span>
                <a href="../../logout.php" class="btn btn-secondary btn-small">Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <nav class="sidebar">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="inicio.php" class="nav-link active">
                    📊 Panel Principal
                </a>
            </li>
            <li class="nav-item">
                <a href="calendario.php" class="nav-link">
                    📅 Calendario de Reservas
                </a>
            </li>
            <li class="nav-item">
                <a href="nueva_reserva.php" class="nav-link">
                    ➕ Nueva Reserva
                </a>
            </li>
            <li class="nav-item">
                <a href="mis_reservas.php" class="nav-link">
                    📋 Mis Reservas
                </a>
            </li>
            <li class="nav-item">
                <a href="mi_reporte.php" class="nav-link">
                    📄 Mi Reporte
                </a>
            </li>
        </ul>
    </nav>

    <!-- Contenido Principal -->
    <main class="main-content">
        <div class="container">
            <h1>Panel de Usuario</h1>
            <p class="mb-4">Bienvenido al Sistema de Gestión Hídrica Comunitaria</p>

            <!-- Tarjetas de estadísticas -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                
                <div class="card">
                    <div class="card-header">
                        📋 Reservas Pendientes
                    </div>
                    <div class="card-body text-center">
                        <h2 style="color: #ffc107; font-size: 2.5rem; margin: 0;"><?php echo $reservas_pendientes; ?></h2>
                        <p>Esperando aprobación</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        📅 Reservas Este Mes
                    </div>
                    <div class="card-body text-center">
                        <h2 style="color: #007bff; font-size: 2.5rem; margin: 0;"><?php echo $reservas_mes; ?></h2>
                        <p><?php echo date('F Y'); ?></p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        ⏱️ Horas Bombeadas
                    </div>
                    <div class="card-body text-center">
                        <h2 style="color: #28a745; font-size: 2.5rem; margin: 0;"><?php echo number_format($horas_mes, 1); ?></h2>
                        <p>Horas este mes</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        💧 Litros Bombeados
                    </div>
                    <div class="card-body text-center">
                        <h2 style="color: #17a2b8; font-size: 2rem; margin: 0;"><?php echo number_format($litros_mes); ?></h2>
                        <p>Litros este mes</p>
                    </div>
                </div>

            </div>

            <!-- Acciones rápidas -->
            <div class="card mb-4">
                <div class="card-header">
                    ⚡ Acciones Rápidas
                </div>
                <div class="card-body">
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <a href="nueva_reserva.php" class="btn btn-primary">
                            ➕ Nueva Reserva de Bombeo
                        </a>
                        <a href="calendario.php" class="btn btn-success">
                            📅 Ver Calendario
                        </a>
                        <a href="mis_reservas.php" class="btn btn-warning">
                            📋 Gestionar Mis Reservas
                        </a>
                        <a href="mi_reporte.php" class="btn btn-secondary">
                            📄 Generar Mi Reporte
                        </a>
                    </div>
                </div>
            </div>

            <!-- Próximas reservas -->
            <div class="card">
                <div class="card-header">
                    📅 Mis Próximas Reservas
                </div>
                <div class="card-body">
                    <?php if (count($proximas_reservas) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Horario</th>
                                        <th>Estado</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proximas_reservas as $reserva): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($reserva['fecha'])); ?></td>
                                            <td>
                                                <?php echo date('H:i', strtotime($reserva['hora_inicio'])); ?> - 
                                                <?php echo date('H:i', strtotime($reserva['hora_fin'])); ?>
                                            </td>
                                            <td>
                                                <span class="estado-<?php echo $reserva['estado']; ?>">
                                                    <?php echo ucfirst($reserva['estado']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($reserva['observaciones'] ?? 'Sin observaciones'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            <a href="mis_reservas.php" class="btn btn-primary btn-small">Ver todas mis reservas</a>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 40px; color: #6c757d;">
                            <h3>📅 No tienes reservas programadas</h3>
                            <p>¡Haz tu primera reserva para bombeo de agua!</p>
                            <a href="nueva_reserva.php" class="btn btn-primary">Crear Nueva Reserva</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información del sistema -->
            <div class="card">
                <div class="card-header">
                    ℹ️ Información del Sistema
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div>
                            <h4>Cómo hacer una reserva:</h4>
                            <ol style="padding-left: 20px; line-height: 1.8;">
                                <li>Ve al calendario de reservas</li>
                                <li>Selecciona una fecha disponible</li>
                                <li>Elige el horario que necesites</li>
                                <li>Espera la aprobación del administrador</li>
                            </ol>
                        </div>
                        <div>
                            <h4>Horarios Disponibles:</h4>
                            <ul style="list-style: none; padding-left: 0; line-height: 1.8;">
                                <li>06:00 - 18:00</li>
                                
                            </ul>
                        </div>
                        <div>
                            <h4>Información Importante:</h4>
                            <ul style="list-style: none; padding-left: 0; line-height: 1.8;">
                                <li>Capacidad: ~52,000 litros/hora</li>
                                <li>Duración mínima: 1 hora</li>
                                <li>Reservar con 24h de anticipación</li>
                                <li>Aprobación requerida del administrador</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Función simple para mostrar/ocultar sidebar en móviles
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>
</html>