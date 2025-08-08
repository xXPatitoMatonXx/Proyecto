<?php
/**
 * Panel de Administración - Diseño Profesional
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

session_start();
require_once '../../config/conexion.php';
require_once '../../config/config.php';
require_once '../../funciones/sesiones.php';

// Verificar que sea administrador
requerir_admin();

$usuario = obtener_usuario_actual();

// Definir array de meses en español
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Obtener estadísticas generales
$mes_actual = date('n');
$año_actual = date('Y');

// Total de usuarios activos
$query_usuarios = "SELECT COUNT(*) as total FROM usuarios WHERE activo = 1 AND id_rol = 2";
$total_usuarios = $conexion->query($query_usuarios)->fetch_assoc()['total'];

// Reservas pendientes de aprobación
$query_pendientes = "SELECT COUNT(*) as total FROM reserva WHERE estado = 'pendiente'";
$reservas_pendientes = $conexion->query($query_pendientes)->fetch_assoc()['total'];

// Reservas para hoy
$query_hoy = "SELECT COUNT(*) as total FROM reserva WHERE fecha = CURDATE() AND estado = 'aprobada'";
$reservas_hoy = $conexion->query($query_hoy)->fetch_assoc()['total'];

// Bombeos realizados este mes
$query_bombeos = "SELECT 
    COUNT(*) as total_bombeos,
    SUM(horas_bombeadas) as total_horas,
    SUM(litros_bombeados) as total_litros
    FROM registro_bombeo 
    WHERE MONTH(fecha_bombeo) = ? AND YEAR(fecha_bombeo) = ?";
$stmt_bombeos = $conexion->prepare($query_bombeos);
$stmt_bombeos->bind_param("ii", $mes_actual, $año_actual);
$stmt_bombeos->execute();
$datos_bombeos = $stmt_bombeos->get_result()->fetch_assoc();

// Últimas solicitudes de reserva
$query_ultimas = "SELECT r.*, u.nombre_completo 
    FROM reserva r 
    INNER JOIN usuarios u ON r.id_usuario = u.id_usuario 
    WHERE r.estado = 'pendiente'
    ORDER BY r.fecha_creacion DESC 
    LIMIT 5";
$ultimas_solicitudes = $conexion->query($query_ultimas)->fetch_all(MYSQLI_ASSOC);

// Próximos bombeos programados
$query_proximos = "SELECT r.*, u.nombre_completo 
    FROM reserva r 
    INNER JOIN usuarios u ON r.id_usuario = u.id_usuario 
    WHERE r.estado = 'aprobada' AND r.fecha >= CURDATE()
    ORDER BY r.fecha ASC, r.hora_inicio ASC 
    LIMIT 5";
$proximos_bombeos = $conexion->query($query_proximos)->fetch_all(MYSQLI_ASSOC);

$titulo_pagina = "Panel de Administración";

// Incluir header
include '../../includes/header.php';
?>

<!-- Incluir navegación -->
<?php include '../../includes/nav.php'; ?>

<!-- Contenido Principal -->
<main class="main-content">
    <div class="container">
        
        <!-- Encabezado de página -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-2">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Panel de Administración
                </h1>
                <p class="text-muted mb-0">Sistema de Gestión Hídrica Comunitaria - Cañada de Flores</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="location.reload()" class="btn btn-outline-primary btn-small">
                    <i class="bi bi-arrow-clockwise"></i>
                    Actualizar
                </button>
            </div>
        </div>

        <!-- Alertas importantes -->
        <?php if ($reservas_pendientes > 0): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                    <strong>Atención:</strong> Tienes <?php echo $reservas_pendientes; ?> solicitud(es) de bombeo pendiente(s) de revisar.
                    <div class="mt-2">
                        <a href="solicitudes.php" class="btn btn-warning btn-small">
                            <i class="bi bi-eye"></i>
                            Ver Solicitudes
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($reservas_hoy > 0): ?>
            <div class="alert alert-info">
                <i class="bi bi-calendar-check-fill"></i>
                <div>
                    <strong>Hoy:</strong> Hay <?php echo $reservas_hoy; ?> bombeo(s) programado(s) para hoy.
                    <div class="mt-2">
                        <a href="registro_bombeo.php" class="btn btn-info btn-small">
                            <i class="bi bi-gear-fill"></i>
                            Registrar Bombeos
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tarjetas de estadísticas -->
        <div class="grid-auto-fit mb-4">
            
            <div class="card dashboard-card">
                <div class="card-header">
                    <i class="bi bi-people-fill"></i>
                    Usuarios Activos
                </div>
                <div class="card-body text-center">
                    <h2 style="color: var(--primary-color); font-size: 2.5rem; margin: 0;"><?php echo $total_usuarios; ?></h2>
                    <p class="text-muted mb-3">Socios registrados</p>
                    <a href="usuarios.php" class="btn btn-primary btn-small">
                        <i class="bi bi-gear"></i>
                        Gestionar
                    </a>
                </div>
            </div>

            <div class="card dashboard-card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i>
                    Solicitudes Pendientes
                </div>
                <div class="card-body text-center">
                    <h2 style="color: var(--warning-color); font-size: 2.5rem; margin: 0;"><?php echo $reservas_pendientes; ?></h2>
                    <p class="text-muted mb-3">Esperando aprobación</p>
                    <a href="solicitudes.php" class="btn btn-warning btn-small">
                        <i class="bi bi-eye"></i>
                        Revisar
                    </a>
                </div>
            </div>

            <div class="card dashboard-card">
                <div class="card-header">
                    <i class="bi bi-gear-fill"></i>
                    Bombeos Este Mes
                </div>
                <div class="card-body text-center">
                    <h2 style="color: var(--success-color); font-size: 2.5rem; margin: 0;"><?php echo $datos_bombeos['total_bombeos'] ?? 0; ?></h2>
                    <p class="text-muted mb-3">Sesiones realizadas</p>
                    <a href="reportes.php" class="btn btn-success btn-small">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        Ver Reportes
                    </a>
                </div>
            </div>

            <div class="card dashboard-card">
                <div class="card-header">
                    <i class="bi bi-droplet-fill"></i>
                    Litros Bombeados
                </div>
                <div class="card-body text-center">
                    <h2 style="color: var(--info-color); font-size: 1.8rem; margin: 0;">
                        <?php echo number_format($datos_bombeos['total_litros'] ?? 0); ?>
                    </h2>
                    <p class="text-muted mb-3">Litros este mes</p>
                    <small class="text-muted"><?php echo number_format($datos_bombeos['total_horas'] ?? 0, 1); ?> horas</small>
                </div>
            </div>

        </div>

        <!-- Acciones rápidas -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-lightning-fill"></i>
                Acciones Rápidas
            </div>
            <div class="card-body">
                <div class="d-flex gap-3 flex-wrap">
                    <a href="solicitudes.php" class="btn btn-warning">
                        <i class="bi bi-clipboard-check"></i>
                        Revisar Solicitudes (<?php echo $reservas_pendientes; ?>)
                    </a>
                    <a href="registro_bombeo.php" class="btn btn-success">
                        <i class="bi bi-gear-fill"></i>
                        Registrar Bombeo de Hoy
                    </a>
                    <a href="usuarios.php" class="btn btn-primary">
                        <i class="bi bi-people"></i>
                        Gestionar Usuarios
                    </a>
                    <a href="reportes.php" class="btn btn-secondary">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        Generar Reportes
                    </a>
                </div>
            </div>
        </div>

        <div class="grid-2 mb-4">
            
            <!-- Últimas solicitudes -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clipboard-data"></i>
                    Últimas Solicitudes Pendientes
                </div>
                <div class="card-body">
                    <?php if (count($ultimas_solicitudes) > 0): ?>
                        <div style="max-height: 350px; overflow-y: auto;">
                            <?php foreach ($ultimas_solicitudes as $solicitud): ?>
                                <div class="d-flex justify-content-between align-items-center p-3" style="border-bottom: 1px solid var(--border-light); margin: 0 -0.5rem;">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="bi bi-person-circle text-muted"></i>
                                            <strong><?php echo htmlspecialchars($solicitud['nombre_completo']); ?></strong>
                                        </div>
                                        <div class="d-flex align-items-center gap-3 text-muted" style="font-size: 0.85rem;">
                                            <span>
                                                <i class="bi bi-calendar3"></i>
                                                <?php echo date('d/m/Y', strtotime($solicitud['fecha'])); ?>
                                            </span>
                                            <span>
                                                <i class="bi bi-clock"></i>
                                                <?php echo date('H:i', strtotime($solicitud['hora_inicio'])); ?>-<?php echo date('H:i', strtotime($solicitud['hora_fin'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <a href="solicitudes.php?id=<?php echo $solicitud['id_reserva']; ?>" class="btn btn-primary btn-small">
                                            <i class="bi bi-eye"></i>
                                            Revisar
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-footer">
                            <a href="solicitudes.php" class="btn btn-warning btn-small">
                                <i class="bi bi-list-check"></i>
                                Ver todas las solicitudes
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle" style="font-size: 3rem; color: var(--success-color); opacity: 0.5;"></i>
                            <h4 class="mt-3 text-muted">No hay solicitudes pendientes</h4>
                            <p class="text-muted">Todas las solicitudes han sido procesadas.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Próximos bombeos -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-calendar-event"></i>
                    Próximos Bombeos Programados
                </div>
                <div class="card-body">
                    <?php if (count($proximos_bombeos) > 0): ?>
                        <div style="max-height: 350px; overflow-y: auto;">
                            <?php foreach ($proximos_bombeos as $bombeo): ?>
                                <div class="d-flex justify-content-between align-items-center p-3" style="border-bottom: 1px solid var(--border-light); margin: 0 -0.5rem;">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="bi bi-person-circle text-muted"></i>
                                            <strong><?php echo htmlspecialchars($bombeo['nombre_completo']); ?></strong>
                                        </div>
                                        <div class="d-flex align-items-center gap-3 text-muted" style="font-size: 0.85rem;">
                                            <span>
                                                <i class="bi bi-calendar3"></i>
                                                <?php echo date('d/m/Y', strtotime($bombeo['fecha'])); ?>
                                            </span>
                                            <span>
                                                <i class="bi bi-clock"></i>
                                                <?php echo date('H:i', strtotime($bombeo['hora_inicio'])); ?>-<?php echo date('H:i', strtotime($bombeo['hora_fin'])); ?>
                                            </span>
                                            <?php if ($bombeo['fecha'] == date('Y-m-d')): ?>
                                                <span class="estado-ejecutada" style="font-size: 0.7rem;">HOY</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="estado-aprobada">Aprobada</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-footer">
                            <a href="registro_bombeo.php" class="btn btn-success btn-small">
                                <i class="bi bi-gear-fill"></i>
                                Registrar bombeos
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x" style="font-size: 3rem; color: var(--text-muted); opacity: 0.5;"></i>
                            <h4 class="mt-3 text-muted">No hay bombeos programados</h4>
                            <p class="text-muted">No hay reservas aprobadas próximamente.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Resumen del mes -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-bar-chart-fill"></i>
                Resumen del Mes (<?php echo isset($meses[$mes_actual]) ? $meses[$mes_actual] . ' ' . $año_actual : 'Mes ' . $mes_actual . ' ' . $año_actual; ?>)
            </div>
            <div class="card-body">
                <div class="grid-4 text-center">
                    <div class="p-4" style="background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); color: white; border-radius: 12px;">
                        <i class="bi bi-gear-fill" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.8;"></i>
                        <h3 style="margin: 0;"><?php echo $datos_bombeos['total_bombeos'] ?? 0; ?></h3>
                        <p style="margin: 0; opacity: 0.9;">Bombeos Realizados</p>
                    </div>
                    <div class="p-4" style="background: linear-gradient(135deg, var(--success-color), #047857); color: white; border-radius: 12px;">
                        <i class="bi bi-clock-fill" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.8;"></i>
                        <h3 style="margin: 0;"><?php echo number_format($datos_bombeos['total_horas'] ?? 0, 1); ?></h3>
                        <p style="margin: 0; opacity: 0.9;">Horas Bombeadas2</p>
                    </div>
                    <div class="p-4" style="background: linear-gradient(135deg, var(--info-color), #0369a1); color: white; border-radius: 12px;">
                        <i class="bi bi-droplet-fill" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.8;"></i>
                        <h3 style="margin: 0;"><?php echo number_format($datos_bombeos['total_litros'] ?? 0); ?></h3>
                        <p style="margin: 0; opacity: 0.9;">Litros Bombeados</p>
                    </div>
                    <div class="p-4" style="background: linear-gradient(135deg, var(--secondary-color), #475569); color: white; border-radius: 12px;">
                        <i class="bi bi-people-fill" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.8;"></i>
                        <h3 style="margin: 0;"><?php echo $total_usuarios; ?></h3>
                        <p style="margin: 0; opacity: 0.9;">Usuarios Activos</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
// Auto-refresh cada 5 minutos para mantener datos actualizados
setTimeout(() => {
    location.reload();
}, 300000);

// Animaciones de entrada
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
});

// Funcionalidad mejorada para las tarjetas de estadísticas
document.querySelectorAll('.dashboard-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-8px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});
</script>

<?php include '../../includes/footer.php'; ?>