<?php
/**
 * Gestión de Solicitudes de Bombeo - Administrador
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

session_start();
require_once '../../config/conexion.php';
require_once '../../config/config.php';
require_once '../../funciones/sesiones.php';

// Verificar que sea administrador
requerir_admin();

$usuario = obtener_usuario_actual();
$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones (aprobar, rechazar, cancelar)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id_reserva = (int)($_POST['id_reserva'] ?? 0);
    $observaciones_admin = $_POST['observaciones_admin'] ?? '';
    
    if ($id_reserva > 0) {
        switch ($accion) {
            case 'aprobar':
                $query = "UPDATE reserva SET estado = 'aprobada', observaciones = ? WHERE id_reserva = ? AND estado = 'pendiente'";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("si", $observaciones_admin, $id_reserva);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $mensaje = "Solicitud aprobada exitosamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al aprobar la solicitud.";
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'rechazar':
                $query = "UPDATE reserva SET estado = 'cancelada', observaciones = ? WHERE id_reserva = ? AND estado = 'pendiente'";
                $stmt = $conexion->prepare($query);
                $observaciones_rechazo = "RECHAZADA: " . $observaciones_admin;
                $stmt->bind_param("si", $observaciones_rechazo, $id_reserva);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $mensaje = "Solicitud rechazada.";
                    $tipo_mensaje = "warning";
                } else {
                    $mensaje = "Error al rechazar la solicitud.";
                    $tipo_mensaje = "error";
                }
                break;
        }
    }
}

// Filtros
$filtro_estado = $_GET['estado'] ?? 'pendiente';
$filtro_fecha = $_GET['fecha'] ?? '';

// Construir query con filtros
$where_conditions = ["1=1"];
$params = [];
$types = "";

if (!empty($filtro_estado) && $filtro_estado !== 'todos') {
    $where_conditions[] = "r.estado = ?";
    $params[] = $filtro_estado;
    $types .= "s";
}

if (!empty($filtro_fecha)) {
    $where_conditions[] = "r.fecha = ?";
    $params[] = $filtro_fecha;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Obtener solicitudes
$query = "SELECT r.*, u.nombre_completo, u.rfc, u.telefono
          FROM reserva r 
          INNER JOIN usuarios u ON r.id_usuario = u.id_usuario 
          WHERE $where_clause
          ORDER BY 
            CASE r.estado 
              WHEN 'pendiente' THEN 1 
              WHEN 'aprobada' THEN 2 
              WHEN 'ejecutada' THEN 3 
              WHEN 'cancelada' THEN 4 
            END,
            r.fecha DESC, r.hora_inicio ASC";

$stmt = $conexion->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$solicitudes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Contar solicitudes por estado
$query_conteos = "SELECT estado, COUNT(*) as total FROM reserva GROUP BY estado";
$conteos_resultado = $conexion->query($query_conteos);
$conteos = [];
while ($row = $conteos_resultado->fetch_assoc()) {
    $conteos[$row['estado']] = $row['total'];
}

$titulo_pagina = "Solicitudes de Bombeo";
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
                <h1>Sistema GHC - Administrador</h1>
            </div>
            <div class="user-info">
                <span>👤 <?php echo htmlspecialchars($usuario['nombre']); ?> (Admin)</span>
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
                <a href="solicitudes.php" class="nav-link active">📋 Solicitudes de Bombeo</a>
            </li>
            <li class="nav-item">
                <a href="registro_bombeo.php" class="nav-link">⚙️ Registro de Bombeo</a>
            </li>
            <li class="nav-item">
                <a href="reportes.php" class="nav-link">📄 Reportes</a>
            </li>
        </ul>
    </nav>

    <!-- Contenido Principal -->
    <main class="main-content">
        <div class="container">
            <h1>📋 Solicitudes de Bombeo</h1>
            <p class="mb-4">Gestiona las solicitudes de reserva de bombeo de los usuarios.</p>

            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <!-- Resumen de estados - Ahora clickeables -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div class="card dashboard-card pendiente <?php echo $filtro_estado == 'pendiente' ? 'active' : ''; ?>" 
                     onclick="filtrarPorEstado('pendiente')" 
                     title="Click para ver solicitudes pendientes">
                    <div class="card-body text-center">
                        <h3 style="color: #ffc107; margin: 0;"><?php echo $conteos['pendiente'] ?? 0; ?></h3>
                        <p>Pendientes</p>
                        <small style="color: #999; font-size: 0.8em;">Click para filtrar</small>
                    </div>
                </div>
                <div class="card dashboard-card aprobada <?php echo $filtro_estado == 'aprobada' ? 'active' : ''; ?>" 
                     onclick="filtrarPorEstado('aprobada')" 
                     title="Click para ver solicitudes aprobadas">
                    <div class="card-body text-center">
                        <h3 style="color: #28a745; margin: 0;"><?php echo $conteos['aprobada'] ?? 0; ?></h3>
                        <p>Aprobadas</p>
                        <small style="color: #999; font-size: 0.8em;">Click para filtrar</small>
                    </div>
                </div>
                <div class="card dashboard-card ejecutada <?php echo $filtro_estado == 'ejecutada' ? 'active' : ''; ?>" 
                     onclick="filtrarPorEstado('ejecutada')" 
                     title="Click para ver solicitudes ejecutadas">
                    <div class="card-body text-center">
                        <h3 style="color: #17a2b8; margin: 0;"><?php echo $conteos['ejecutada'] ?? 0; ?></h3>
                        <p>Ejecutadas</p>
                        <small style="color: #999; font-size: 0.8em;">Click para filtrar</small>
                    </div>
                </div>
                <div class="card dashboard-card cancelada <?php echo $filtro_estado == 'cancelada' ? 'active' : ''; ?>" 
                     onclick="filtrarPorEstado('cancelada')" 
                     title="Click para ver solicitudes canceladas">
                    <div class="card-body text-center">
                        <h3 style="color: #dc3545; margin: 0;"><?php echo $conteos['cancelada'] ?? 0; ?></h3>
                        <p>Canceladas</p>
                        <small style="color: #999; font-size: 0.8em;">Click para filtrar</small>
                    </div>
                </div>
            </div>

            <!-- Botón para mostrar todas -->
            <?php if ($filtro_estado !== 'todos'): ?>
                <div class="mb-3">
                    <button onclick="filtrarPorEstado('todos')" class="btn btn-outline-primary">
                        📋 Ver todas las solicitudes
                    </button>
                </div>
            <?php endif; ?>

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
                                <option value="todos" <?php echo $filtro_estado == 'todos' ? 'selected' : ''; ?>>Todos los estados</option>
                                <option value="pendiente" <?php echo $filtro_estado == 'pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                                <option value="aprobada" <?php echo $filtro_estado == 'aprobada' ? 'selected' : ''; ?>>Aprobadas</option>
                                <option value="ejecutada" <?php echo $filtro_estado == 'ejecutada' ? 'selected' : ''; ?>>Ejecutadas</option>
                                <option value="cancelada" <?php echo $filtro_estado == 'cancelada' ? 'selected' : ''; ?>>Canceladas</option>
                            </select>
                        </div>
                        
                        <div class="input-group" style="flex: 1; min-width: 200px;">
                            <label for="fecha">Fecha:</label>
                            <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($filtro_fecha); ?>">
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="solicitudes.php" class="btn btn-secondary">Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de solicitudes -->
            <div class="card">
                <div class="card-header">
                    📋 Solicitudes de Bombeo (<?php echo count($solicitudes); ?> resultados)
                    <?php if ($filtro_estado !== 'todos'): ?>
                        <span class="estado-<?php echo $filtro_estado; ?>" style="margin-left: 10px; padding: 2px 8px; border-radius: 4px; font-size: 0.8em;">
                            Filtrado por: <?php echo ucfirst($filtro_estado); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (count($solicitudes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Fecha</th>
                                        <th>Horario</th>
                                        <th>Estado</th>
                                        <th>Solicitud</th>
                                        <th>Observaciones</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($solicitudes as $solicitud): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($solicitud['nombre_completo']); ?></strong><br>
                                                <small style="color: #666;">
                                                    RFC: <?php echo htmlspecialchars($solicitud['rfc']); ?><br>
                                                    Tel: <?php echo htmlspecialchars($solicitud['telefono'] ?? 'N/A'); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php 
                                                $fecha = new DateTime($solicitud['fecha']);
                                                echo $fecha->format('d/m/Y');
                                                ?>
                                                <br>
                                                <small style="color: #666;">
                                                    <?php
                                                    $hoy = new DateTime();
                                                    $diff = $hoy->diff($fecha);
                                                    if ($fecha < $hoy) {
                                                        echo "Hace " . $diff->days . " día(s)";
                                                    } elseif ($fecha == $hoy) {
                                                        echo "HOY";
                                                    } else {
                                                        echo "En " . $diff->days . " día(s)";
                                                    }
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php echo date('H:i', strtotime($solicitud['hora_inicio'])); ?> - 
                                                <?php echo date('H:i', strtotime($solicitud['hora_fin'])); ?>
                                                <br>
                                                <small style="color: #666;">
                                                    ~<?php echo number_format(((strtotime($solicitud['hora_fin']) - strtotime($solicitud['hora_inicio'])) / 3600) * LITROS_POR_HORA); ?> litros
                                                </small>
                                            </td>
                                            <td>
                                                <span class="estado-<?php echo $solicitud['estado']; ?>">
                                                    <?php echo ucfirst($solicitud['estado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small style="color: #666;">
                                                    <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_creacion'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if (!empty($solicitud['observaciones'])): ?>
                                                    <small><?php echo htmlspecialchars($solicitud['observaciones']); ?></small>
                                                <?php else: ?>
                                                    <small style="color: #999;">Sin observaciones</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($solicitud['estado'] == 'pendiente'): ?>
                                                    <div style="display: flex; gap: 5px; flex-direction: column;">
                                                        <button onclick="abrirModalAprobacion(<?php echo $solicitud['id_reserva']; ?>, '<?php echo addslashes($solicitud['nombre_completo']); ?>')" 
                                                                class="btn btn-success btn-small">
                                                            ✓ Aprobar
                                                        </button>
                                                        <button onclick="abrirModalRechazo(<?php echo $solicitud['id_reserva']; ?>, '<?php echo addslashes($solicitud['nombre_completo']); ?>')" 
                                                                class="btn btn-danger btn-small">
                                                            ✗ Rechazar
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <small style="color: #666;">
                                                        <?php echo ucfirst($solicitud['estado']); ?>
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
                            <h3>📋 No hay solicitudes</h3>
                            <p>No se encontraron solicitudes con los filtros aplicados.</p>
                            <?php if ($filtro_estado !== 'todos'): ?>
                                <button onclick="filtrarPorEstado('todos')" class="btn btn-primary">
                                    Ver todas las solicitudes
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal para aprobar solicitud -->
    <div id="modalAprobar" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%;">
            <h3>✓ Aprobar Solicitud</h3>
            <p>¿Estás seguro de que deseas aprobar la solicitud de <strong id="nombreUsuarioAprobar"></strong>?</p>
            
            <form method="POST" id="formAprobar">
                <input type="hidden" name="accion" value="aprobar">
                <input type="hidden" name="id_reserva" id="idReservaAprobar">
                
                <div class="input-group">
                    <label for="observacionesAprobar">Observaciones (opcional):</label>
                    <textarea id="observacionesAprobar" name="observaciones_admin" rows="3" placeholder="Comentarios adicionales..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="cerrarModal('modalAprobar')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-success">Aprobar Solicitud</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para rechazar solicitud -->
    <div id="modalRechazar" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%;">
            <h3>✗ Rechazar Solicitud</h3>
            <p>¿Estás seguro de que deseas rechazar la solicitud de <strong id="nombreUsuarioRechazar"></strong>?</p>
            
            <form method="POST" id="formRechazar">
                <input type="hidden" name="accion" value="rechazar">
                <input type="hidden" name="id_reserva" id="idReservaRechazar">
                
                <div class="input-group">
                    <label for="observacionesRechazar">Motivo del rechazo: *</label>
                    <textarea id="observacionesRechazar" name="observaciones_admin" rows="3" required placeholder="Explica el motivo del rechazo..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="cerrarModal('modalRechazar')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Rechazar Solicitud</button>
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
            
            // Mantener el filtro de fecha si existe
            const fechaActual = document.getElementById('fecha').value;
            if (fechaActual) {
                url.searchParams.set('fecha', fechaActual);
            } else {
                url.searchParams.delete('fecha');
            }
            
            // Redirigir con los nuevos parámetros
            window.location.href = url.toString();
        }
        
        // Actualizar el select cuando se cambie desde las tarjetas
        function actualizarSelectEstado(estado) {
            document.getElementById('estado').value = estado;
        }
        
        function abrirModalAprobacion(idReserva, nombreUsuario) {
            document.getElementById('idReservaAprobar').value = idReserva;
            document.getElementById('nombreUsuarioAprobar').textContent = nombreUsuario;
            document.getElementById('modalAprobar').style.display = 'block';
        }
        
        function abrirModalRechazo(idReserva, nombreUsuario) {
            document.getElementById('idReservaRechazar').value = idReserva;
            document.getElementById('nombreUsuarioRechazar').textContent = nombreUsuario;
            document.getElementById('modalRechazar').style.display = 'block';
        }
        
        function cerrarModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modalAprobar = document.getElementById('modalAprobar');
            const modalRechazar = document.getElementById('modalRechazar');
            if (event.target == modalAprobar) {
                modalAprobar.style.display = 'none';
            }
            if (event.target == modalRechazar) {
                modalRechazar.style.display = 'none';
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
            const estadoActual = urlParams.get('estado') || 'pendiente';
            document.getElementById('estado').value = estadoActual;
        });
    </script>
</body>
</html>