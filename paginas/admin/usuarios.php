<?php
/**
 * Gestión de Usuarios - Administrador
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

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    
    switch ($accion) {
        case 'activar':
            $query = "UPDATE usuarios SET activo = 1 WHERE id_usuario = ? AND id_usuario != ?";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param("ii", $id_usuario, $usuario_admin['id']);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $mensaje = "Usuario activado exitosamente.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al activar el usuario.";
                $tipo_mensaje = "error";
            }
            break;
            
        case 'desactivar':
            $query = "UPDATE usuarios SET activo = 0 WHERE id_usuario = ? AND id_usuario != ?";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param("ii", $id_usuario, $usuario_admin['id']);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $mensaje = "Usuario desactivado exitosamente.";
                $tipo_mensaje = "warning";
            } else {
                $mensaje = "Error al desactivar el usuario.";
                $tipo_mensaje = "error";
            }
            break;
            
        case 'agregar_socio':
            $nombre_completo = limpiar_entrada($_POST['nombre_completo']);
            $rfc = limpiar_entrada(strtoupper($_POST['rfc']));
            $observaciones = limpiar_entrada($_POST['observaciones']);
            
            if (validar_rfc($rfc)) {
                $query = "INSERT INTO socios_autorizados (nombre_completo, rfc, observaciones) VALUES (?, ?, ?)";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("sss", $nombre_completo, $rfc, $observaciones);
                if ($stmt->execute()) {
                    $mensaje = "Socio autorizado agregado exitosamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al agregar socio autorizado (posible RFC duplicado).";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "RFC no válido.";
                $tipo_mensaje = "error";
            }
            break;
    }
}

// Obtener usuarios
$query_usuarios = "SELECT u.*, 
    (SELECT COUNT(*) FROM reserva WHERE id_usuario = u.id_usuario) as total_reservas,
    (SELECT COUNT(*) FROM reserva WHERE id_usuario = u.id_usuario AND estado = 'ejecutada') as reservas_ejecutadas
    FROM usuarios u 
    ORDER BY u.activo DESC, u.nombre_completo ASC";
$usuarios = $conexion->query($query_usuarios)->fetch_all(MYSQLI_ASSOC);

// Obtener socios autorizados
$query_socios = "SELECT * FROM socios_autorizados ORDER BY activo DESC, nombre_completo ASC";
$socios_autorizados = $conexion->query($query_socios)->fetch_all(MYSQLI_ASSOC);

// Estadísticas
$total_usuarios = count(array_filter($usuarios, function($u) { return $u['activo'] == 1 && $u['id_rol'] == 2; }));
$total_admins = count(array_filter($usuarios, function($u) { return $u['activo'] == 1 && $u['id_rol'] == 1; }));
$total_socios_autorizados = count(array_filter($socios_autorizados, function($s) { return $s['activo'] == 1; }));

$titulo_pagina = "Gestión de Usuarios";
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
                <a href="usuarios.php" class="nav-link active">👥 Gestión de Usuarios</a>
            </li>
            <li class="nav-item">
                <a href="solicitudes.php" class="nav-link">📋 Solicitudes de Bombeo</a>
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
            <h1>👥 Gestión de Usuarios</h1>
            <p class="mb-4">Administra usuarios registrados y socios autorizados del sistema.</p>

            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #007bff; margin: 0;"><?php echo $total_usuarios; ?></h3>
                        <p>Usuarios Activos</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #28a745; margin: 0;"><?php echo $total_admins; ?></h3>
                        <p>Administradores</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #ffc107; margin: 0;"><?php echo $total_socios_autorizados; ?></h3>
                        <p>Socios Autorizados</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #17a2b8; margin: 0;"><?php echo count($usuarios); ?></h3>
                        <p>Total Registrados</p>
                    </div>
                </div>
            </div>

            <!-- Socios Autorizados -->
            <div class="card mb-4">
                <div class="card-header">
                    👨‍👩‍👧‍👦 Socios Autorizados
                    <button onclick="abrirModalSocio()" class="btn btn-primary btn-small" style="float: right;">
                        ➕ Agregar Socio
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nombre Completo</th>
                                    <th>RFC</th>
                                    <th>Fecha Registro</th>
                                    <th>Estado</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($socios_autorizados as $socio): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($socio['nombre_completo']); ?></td>
                                        <td><?php echo htmlspecialchars($socio['rfc']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($socio['fecha_registro'])); ?></td>
                                        <td>
                                            <span class="estado-<?php echo $socio['activo'] ? 'aprobada' : 'cancelada'; ?>">
                                                <?php echo $socio['activo'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($socio['observaciones'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Usuarios Registrados -->
            <div class="card">
                <div class="card-header">
                    👥 Usuarios Registrados en el Sistema
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Información</th>
                                    <th>Rol</th>
                                    <th>Registro</th>
                                    <th>Actividad</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($usuario['nombre_completo']); ?></strong><br>
                                            <small style="color: #666;">@<?php echo htmlspecialchars($usuario['username']); ?></small>
                                        </td>
                                        <td>
                                            <small style="color: #666;">
                                                RFC: <?php echo htmlspecialchars($usuario['rfc']); ?><br>
                                                Tel: <?php echo htmlspecialchars($usuario['telefono'] ?? 'N/A'); ?><br>
                                                <?php if (!empty($usuario['domicilio'])): ?>
                                                    Dir: <?php echo htmlspecialchars(substr($usuario['domicilio'], 0, 30)) . (strlen($usuario['domicilio']) > 30 ? '...' : ''); ?>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="estado-<?php echo $usuario['id_rol'] == 1 ? 'ejecutada' : 'aprobada'; ?>">
                                                <?php echo $usuario['id_rol'] == 1 ? 'Admin' : 'Usuario'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?><br>
                                            <small style="color: #666;">
                                                <?php
                                                $dias = (time() - strtotime($usuario['fecha_registro'])) / (60 * 60 * 24);
                                                echo "Hace " . round($dias) . " día(s)";
                                                ?>
                                            </small>
                                        </td>
                                        <td>
                                            <strong style="color: #007bff;"><?php echo $usuario['total_reservas']; ?></strong> reservas<br>
                                            <small style="color: #28a745;"><?php echo $usuario['reservas_ejecutadas']; ?> ejecutadas</small>
                                        </td>
                                        <td>
                                            <span class="estado-<?php echo $usuario['activo'] ? 'aprobada' : 'cancelada'; ?>">
                                                <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($usuario['id_usuario'] != $usuario_admin['id']): ?>
                                                <?php if ($usuario['activo']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="accion" value="desactivar">
                                                        <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                                                        <button type="submit" onclick="return confirm('¿Desactivar este usuario?')" class="btn btn-warning btn-small">
                                                            Desactivar
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="accion" value="activar">
                                                        <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                                                        <button type="submit" onclick="return confirm('¿Activar este usuario?')" class="btn btn-success btn-small">
                                                            Activar
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <small style="color: #666;">Eres tú</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal para agregar socio -->
    <div id="modalSocio" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%;">
            <h3>➕ Agregar Socio Autorizado</h3>
            <p>Agrega un nuevo socio autorizado para que pueda registrarse en el sistema.</p>
            
            <form method="POST" id="formSocio">
                <input type="hidden" name="accion" value="agregar_socio">
                
                <div class="input-group">
                    <label for="nombre_completo">Nombre Completo: *</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" required placeholder="Ej: Juan Pérez García">
                </div>
                
                <div class="input-group">
                    <label for="rfc">RFC: *</label>
                    <input type="text" id="rfc" name="rfc" required maxlength="13" placeholder="Ej: PEGJ850315A1B" style="text-transform: uppercase;">
                </div>
                
                <div class="input-group">
                    <label for="observaciones">Observaciones:</label>
                    <textarea id="observaciones" name="observaciones" rows="2" placeholder="Información adicional..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="cerrarModalSocio()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agregar Socio</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalSocio() {
            document.getElementById('modalSocio').style.display = 'block';
        }
        
        function cerrarModalSocio() {
            document.getElementById('modalSocio').style.display = 'none';
            document.getElementById('formSocio').reset();
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal  = document.getElementById('modalSocio');
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