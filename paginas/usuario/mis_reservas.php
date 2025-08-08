<?php
/**
 * Mis Reservas - Usuario
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
$tipo_mensaje = '';

// Procesar cancelación de reserva
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'cancelar') {
    $id_reserva = (int)$_POST['id_reserva'];
    
    // Verificar que la reserva pertenezca al usuario y se pueda cancelar
    $query_verificar = "SELECT * FROM reserva WHERE id_reserva = ? AND id_usuario = ? AND estado IN ('pendiente', 'aprobada') AND fecha > CURDATE()";
    $stmt_verificar = $conexion->prepare($query_verificar);
    $stmt_verificar->bind_param("ii", $id_reserva, $usuario['id']);
    $stmt_verificar->execute();
    $reserva = $stmt_verificar->get_result()->fetch_assoc();
    
    if ($reserva) {
        $query_cancelar = "UPDATE reserva SET estado = 'cancelada', observaciones = CONCAT(COALESCE(observaciones, ''), ' - CANCELADA POR USUARIO') WHERE id_reserva = ?";
        $stmt_cancelar = $conexion->prepare($query_cancelar);
        $stmt_cancelar->bind_param("i", $id_reserva);
        
        if ($stmt_cancelar->execute()) {
            $mensaje = "Reserva cancelada exitosamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al cancelar la reserva.";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "No se puede cancelar esta reserva.";
        $tipo_mensaje = "error";
    }
}

// Filtros
$filtro_estado = $_GET['estado'] ?? 'todas';
$filtro_mes = $_GET['mes'] ?? '';

// Construir query
$where_conditions = ["r.id_usuario = ?"];
$params = [$usuario['id']];
$types = "i";

if ($filtro_estado !== 'todas') {
    $where_conditions[] = "r.estado = ?";
    $params[] = $filtro_estado;
    $types .= "s";
}

if (!empty($filtro_mes)) {
    $where_conditions[] = "DATE_FORMAT(r.fecha, '%Y-%m') = ?";
    $params[] = $filtro_mes;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Obtener reservas del usuario
$query = "SELECT r.*, rb.horas_bombeadas, rb.litros_bombeados
          FROM reserva r 
          LEFT JOIN registro_bombeo rb ON r.id_reserva = rb.id_reserva
          WHERE $where_clause
          ORDER BY r.fecha DESC, r.hora_inicio DESC";

$stmt = $conexion->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$mis_reservas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Estadísticas del usuario
$query_stats = "SELECT 
    COUNT(*) as total_reservas,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
    SUM(CASE WHEN estado = 'ejecutada' THEN 1 ELSE 0 END) as ejecutadas,
    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas
    FROM reserva WHERE id_usuario = ?";
$stmt_stats = $conexion->prepare($query_stats);
$stmt_stats->bind_param("i", $usuario['id']);
$stmt_stats->execute();
$estadisticas = $stmt_stats->get_result()->fetch_assoc();

$titulo_pagina = "Mis Reservas";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> - Sistema GHC</title>
    <link rel="stylesheet" href="../../css/estilos.css">
    <style>
        .dashboard-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .dashboard-card.active {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        
        .dashboard-card.total:hover {
            border-color: #007bff;
        }
        
        .dashboard-card.pendiente:hover {
            border-color: #ffc107;
        }
        
        .dashboard-card.aprobada:hover {
            border-color: #28a745;
        }
        
        .dashboard-card.ejecutada:hover {
            border-color: #17a2b8;
        }
        
        .dashboard-card.cancelada:hover {
            border-color: #dc3545;
        }
        
        .dashboard-card.total.active {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        
        .dashboard-card.pendiente.active {
            border-color: #ffc107;
            background-color: #fff9e6;
        }
        
        .dashboard-card.aprobada.active {
            border-color: #28a745;
            background-color: #f0fff4;
        }
        
        .dashboard-card.ejecutada.active {
            border-color: #17a2b8;
            background-color: #e6f7ff;
        }
        
        .dashboard-card.cancelada.active {
            border-color: #dc3545;
            background-color: #fff5f5;
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
                <a href="nueva_reserva.php" class="nav-link">➕ Nueva Reserva</a>
            </li>
            <li class="nav-item">
                <a href="mis_reservas.php" class="nav-link active">📋 Mis Reservas</a>
            </li>
            <li class="nav-item">
                <a href="mi_reporte.php" class="nav-link">📄 Mi Reporte</a>
            </li>
        </ul>
    </nav>

    <!-- Contenido Principal -->
    <main class="main-content">
        <div class="container">
            <h1>📋 Mis Reservas</h1>
            <p class="mb-4">Gestiona todas tus reservas de bombeo del pozo comunitario.</p>

            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas - Ahora clickeables -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div class="card dashboard-card total <?php echo $filtro_estado == 'todas' ? 'active' : ''; ?>" 
                     onclick="filtrarPorEstado('todas')" 
                     title="Click para ver todas las reservas">
                    <div class="card-body text-center">
                        <h3 style="color: #007bff; margin: 0;"><?php echo $estadisticas['total_reservas']; ?></h3>
                        <p>Total</p>
                        <small style="color: #999; font-size: 0.8em;">Click para filtrar</small>
                    </div>
                </div>
                <div class="card dashboard-card pendiente <?php echo $filtro_estado == 'pendiente' ? 'active' : ''; ?>" 
                     onclick="filtrarPorEstado('pendiente')" 
                     title="Click para ver reservas pendientes">
                    <div class="card-body text-center">
                        <h3 style="color: #ffc107; margin: 0;"><?php echo $estadisticas['pendientes']; ?></h3>
                        <p>Pendientes</p>
                        <small style="color: #999; font-size: 0.8em;">Click para filtrar</small>
                    </div>
                </div>
                <div class="card dashboard-card aprobada <?php echo $filtro_estado == 'aprobada' ? 'active' : ''; ?>" 
                     onclick="filtrarPorEstado('aprobada')" 
                     title="Click para ver reservas aprobadas">
                    <div class="card-body text-center">
                        <h3 style="color: #28a745; margin: 0;"><?php echo $estadisticas['aprobadas']; ?></h3>
                        <p>Aprobadas</p>
                        <small style="color: #999; font-size: 0.8em;">Click para filtrar</small>
                    </div>
                </div>
                <div class="card dashboard-card ejecutada <?php echo $filtro_estado == 'ejecutada' ? 'active' : ''; ?>" 
                     onclick="filtrarPorEstado('ejecutada')" 
                     title="Click para ver reservas ejecutadas">
                    <div class="card-body text-center">
                        <h3 style="color: #17a2b8; margin: 0;"><?php echo $estadisticas['ejecutadas']; ?></h3>
                        <p>Ejecutadas</p>
                        <small style="color: #999; font-size: 0.8em;">Click para filtrar</small>
                    </div>
                </div>
                <div class="card dashboard-card cancelada <?php echo $filtro_estado == 'cancelada' ? 'active' : ''; ?>" 
                     onclick="filtrarPorEstado('cancelada')" 
                     title="Click para ver reservas canceladas">
                    <div class="card-body text-center">
                        <h3 style="color: #dc3545; margin: 0;"><?php echo $estadisticas['canceladas']; ?></h3>
                        <p>Canceladas</p>
                        <small style="color: #999; font-size: 0.8em;">Click para filtrar</small>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    🔍 Filtros
                </div>
                <div class="card-body">
                    <form method="GET" id="filtrosForm" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                        <div class="input-group" style="flex: 1; min-width: 200px;">
                            <label for="estado">Estado:</label>
                            <select id="estado" name="estado">
                                <option value="todas" <?php echo $filtro_estado == 'todas' ? 'selected' : ''; ?>>Todas</option>
                                <option value="pendiente" <?php echo $filtro_estado == 'pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                                <option value="aprobada" <?php echo $filtro_estado == 'aprobada' ? 'selected' : ''; ?>>Aprobadas</option>
                                <option value="ejecutada" <?php echo $filtro_estado == 'ejecutada' ? 'selected' : ''; ?>>Ejecutadas</option>
                                <option value="cancelada" <?php echo $filtro_estado == 'cancelada' ? 'selected' : ''; ?>>Canceladas</option>
                            </select>
                        </div>
                        
                        <div class="input-group" style="flex: 1; min-width: 200px;">
                            <label for="mes">Mes:</label>
                            <input type="month" id="mes" name="mes" value="<?php echo htmlspecialchars($filtro_mes); ?>">
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="mis_reservas.php" class="btn btn-secondary">Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Acción rápida -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <a href="nueva_reserva.php" class="btn btn-primary">
                        ➕ Crear Nueva Reserva
                    </a>
                    <a href="calendario.php" class="btn btn-success">
                        📅 Ver Calendario
                    </a>
                </div>
            </div>

            <!-- Lista de reservas -->
            <div class="card">
                <div class="card-header">
                    📋 Mis Reservas (<?php echo count($mis_reservas); ?> resultados)
                    <?php if ($filtro_estado !== 'todas'): ?>
                        <span class="estado-<?php echo $filtro_estado; ?>" style="margin-left: 10px; padding: 2px 8px; border-radius: 4px; font-size: 0.8em;">
                            Filtrado por: <?php echo ucfirst($filtro_estado); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (count($mis_reservas) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Horario</th>
                                        <th>Estado</th>
                                        <th>Litros Estimados</th>
                                        <th>Resultado</th>
                                        <th>Observaciones</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mis_reservas as $reserva): ?>
                                        <tr>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($reserva['fecha'])); ?>
                                                <br>
                                                <small style="color: #666;">
                                                    <?php
                                                    $fecha_reserva = new DateTime($reserva['fecha']);
                                                    $hoy = new DateTime();
                                                    if ($fecha_reserva < $hoy) {
                                                        echo "Pasada";
                                                    } elseif ($fecha_reserva->format('Y-m-d') == $hoy->format('Y-m-d')) {
                                                        echo "HOY";
                                                    } else {
                                                        $diff = $hoy->diff($fecha_reserva);
                                                        echo "En " . $diff->days . " día(s)";
                                                    }
                                                    ?>
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
                                                <br>
                                                <small style="color: #666;">
                                                    <?php echo date('d/m H:i', strtotime($reserva['fecha_creacion'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php 
                                                $horas = (strtotime($reserva['hora_fin']) - strtotime($reserva['hora_inicio'])) / 3600;
                                                $litros_estimados = $horas * LITROS_POR_HORA;
                                                echo number_format($litros_estimados);
                                                ?>
                                                <br>
                                                <small style="color: #666;">litros</small>
                                            </td>
                                            <td>
                                                <?php if ($reserva['estado'] == 'ejecutada' && $reserva['litros_bombeados']): ?>
                                                    <strong style="color: #28a745;">
                                                        <?php echo number_format($reserva['litros_bombeados']); ?> L
                                                    </strong>
                                                    <br>
                                                    <small style="color: #666;">
                                                        <?php echo number_format($reserva['horas_bombeadas'], 1); ?> hrs
                                                    </small>
                                                <?php else: ?>
                                                    <small style="color: #999;">
                                                        <?php
                                                        switch ($reserva['estado']) {
                                                            case 'pendiente': echo 'Esperando aprobación'; break;
                                                            case 'aprobada': echo 'Programada'; break;
                                                            case 'cancelada': echo 'Cancelada'; break;
                                                            default: echo 'Sin datos'; break;
                                                        }
                                                        ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($reserva['observaciones'])): ?>
                                                    <small><?php echo htmlspecialchars($reserva['observaciones']); ?></small>
                                                <?php else: ?>
                                                    <small style="color: #999;">Sin observaciones</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($reserva['estado'] == 'pendiente' || ($reserva['estado'] == 'aprobada' && $reserva['fecha'] > date('Y-m-d'))): ?>
                                                    <button onclick="confirmarCancelacion(<?php echo $reserva['id_reserva']; ?>)" 
                                                            class="btn btn-danger btn-small">
                                                        ✗ Cancelar
                                                    </button>
                                                <?php else: ?>
                                                    <small style="color: #666;">
                                                        <?php
                                                        switch ($reserva['estado']) {
                                                            case 'ejecutada': echo '✓ Completada'; break;
                                                            case 'cancelada': echo '✗ Cancelada'; break;
                                                            case 'aprobada': echo '⏰ Pasada'; break;
                                                            default: echo '-'; break;
                                                        }
                                                        ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 40px; color: #6c757d;">
                            <h3>📭 No tienes reservas</h3>
                            <p>No se encontraron reservas con los filtros aplicados.</p>
                            <?php if ($filtro_estado !== 'todas'): ?>
                                <button onclick="filtrarPorEstado('todas')" class="btn btn-primary" style="margin-right: 10px;">
                                    Ver todas las reservas
                                </button>
                            <?php endif; ?>
                            <a href="nueva_reserva.php" class="btn btn-primary">Crear Mi Primera Reserva</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de confirmación -->
    <div id="modalCancelar" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; max-width: 400px; width: 90%;">
            <h3>⚠️ Cancelar Reserva</h3>
            <p>¿Estás seguro de que deseas cancelar esta reserva?</p>
            <p><strong>Esta acción no se puede deshacer.</strong></p>
            
            <form method="POST" id="formCancelar">
                <input type="hidden" name="accion" value="cancelar">
                <input type="hidden" name="id_reserva" id="idReservaCancelar">
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="cerrarModal()" class="btn btn-secondary">No, mantener</button>
                    <button type="submit" class="btn btn-danger">Sí, cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Función principal para filtrar por estado
        function filtrarPorEstado(estado) {
            // Construir la URL con el filtro de estado
            const url = new URL(window.location);
            url.searchParams.set('estado', estado);
            
            // Mantener el filtro de mes si existe
            const mesActual = document.getElementById('mes').value;
            if (mesActual) {
                url.searchParams.set('mes', mesActual);
            } else {
                url.searchParams.delete('mes');
            }
            
            // Redirigir con los nuevos parámetros
            window.location.href = url.toString();
        }
        
        function confirmarCancelacion(idReserva) {
            document.getElementById('idReservaCancelar').value = idReserva;
            document.getElementById('modalCancelar').style.display = 'block';
        }
        
        function cerrarModal() {
            document.getElementById('modalCancelar').style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalCancelar');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Función para mostrar/ocultar sidebar en móviles
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }
        
        // Sincronizar el select con los filtros de URL al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const estadoActual = urlParams.get('estado') || 'todas';
            document.getElementById('estado').value = estadoActual;
        });
    </script>
</body>
</html>
