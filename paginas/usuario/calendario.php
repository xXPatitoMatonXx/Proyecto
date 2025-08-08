<?php
/**
 * Calendario de Reservas - Usuario
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

// Obtener mes y año actual o del parámetro
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$año = isset($_GET['año']) ? (int)$_GET['año'] : date('Y');

// Validar mes y año
if ($mes < 1 || $mes > 12) $mes = date('n');
if ($año < 2020 || $año > 2030) $año = date('Y');

// Nombres de meses
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Obtener primer día del mes y número de días
$primer_dia = mktime(0, 0, 0, $mes, 1, $año);
$numero_dias = date('t', $primer_dia);
$dia_semana = date('w', $primer_dia); // 0 = domingo

// Obtener reservas del mes
$query_reservas = "SELECT 
    DATE(fecha) as fecha_dia,
    COUNT(*) as total_reservas,
    GROUP_CONCAT(CONCAT(hora_inicio, '-', hora_fin) SEPARATOR ', ') as horarios,
    GROUP_CONCAT(estado SEPARATOR ', ') as estados
    FROM reserva 
    WHERE MONTH(fecha) = ? AND YEAR(fecha) = ?
    GROUP BY DATE(fecha)";

$stmt_reservas = $conexion->prepare($query_reservas);
$stmt_reservas->bind_param("ii", $mes, $año);
$stmt_reservas->execute();
$resultado_reservas = $stmt_reservas->get_result();

$reservas_mes = [];
while ($row = $resultado_reservas->fetch_assoc()) {
    $reservas_mes[$row['fecha_dia']] = $row;
}

// Calcular navegación
$mes_anterior = $mes == 1 ? 12 : $mes - 1;
$año_anterior = $mes == 1 ? $año - 1 : $año;
$mes_siguiente = $mes == 12 ? 1 : $mes + 1;
$año_siguiente = $mes == 12 ? $año + 1 : $año;

$titulo_pagina = "Calendario de Reservas";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> - Sistema GHC</title>
    <link rel="stylesheet" href="../../css/estilos.css">
    <style>
        .calendario {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .calendario-header {
            background: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-mes {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .nav-mes:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        
        .calendario-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }
        
        .dia-header {
            background: #f8f9fa;
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            border-bottom: 1px solid #dee2e6;
            color: #495057;
        }
        
        .dia-celda {
            min-height: 100px;
            border-right: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
            padding: 8px;
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .dia-celda:hover {
            background-color: #f8f9fa;
        }
        
        .dia-numero {
            font-weight: 600;
            margin-bottom: 5px;
            color: #495057;
        }
        
        .dia-otro-mes {
            color: #ced4da;
            background-color: #f8f9fa;
        }
        
        .dia-hoy {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
        }
        
        .dia-con-reservas {
            background-color: #e3f2fd;
        }
        
        .dia-ocupado {
            background-color: #ffebee;
        }
        
        /* Estilos para días pasados (deshabilitados) */
        .dia-pasado {
            background-color: #f5f5f5 !important;
            cursor: not-allowed !important;
            opacity: 0.6;
        }
        
        .dia-pasado:hover {
            background-color: #f5f5f5 !important;
        }
        
        /* Días disponibles (futuro) */
        .dia-disponible {
            position: relative;
        }
        
        .dia-disponible:hover {
            background-color: #e8f5e8 !important;
            border: 2px solid #28a745;
        }
        
        .dia-disponible:hover::after {
            content: '➕ Click para reservar';
            position: absolute;
            bottom: 2px;
            left: 2px;
            right: 2px;
            background: #28a745;
            color: white;
            font-size: 10px;
            padding: 2px;
            border-radius: 3px;
            text-align: center;
        }
        
        .reserva-indicador {
            font-size: 10px;
            padding: 2px 4px;
            border-radius: 3px;
            margin-bottom: 2px;
            display: block;
        }
        
        .reserva-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .reserva-aprobada {
            background-color: #d4edda;
            color: #155724;
        }
        
        .reserva-ejecutada {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .reserva-cancelada {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .leyenda {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 15px;
            background: #f8f9fa;
            flex-wrap: wrap;
        }
        
        .leyenda-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
        }
        
        .leyenda-color {
            width: 16px;
            height: 16px;
            border-radius: 3px;
        }
        
        @media (max-width: 768px) {
            .calendario-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .dia-celda {
                min-height: 80px;
                padding: 4px;
            }
            
            .dia-header {
                padding: 10px 5px;
                font-size: 12px;
            }
            
            .leyenda {
                flex-direction: column;
                gap: 10px;
            }
            
            .dia-disponible:hover::after {
                content: '➕';
                font-size: 14px;
                padding: 1px;
            }
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
                <a href="calendario.php" class="nav-link active">📅 Calendario de Reservas</a>
            </li>
            <li class="nav-item">
                <a href="nueva_reserva.php" class="nav-link">➕ Nueva Reserva</a>
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
            <h1>📅 Calendario de Reservas</h1>
            <p class="mb-4">Visualiza las reservas de bombeo por día y haz clic en una fecha disponible para crear una nueva reserva.</p>

            <!-- Calendario -->
            <div class="calendario">
                <div class="calendario-header">
                    <a href="?mes=<?php echo $mes_anterior; ?>&año=<?php echo $año_anterior; ?>" class="nav-mes">
                        ← Anterior
                    </a>
                    <h2><?php echo $meses[$mes] . ' ' . $año; ?></h2>
                    <a href="?mes=<?php echo $mes_siguiente; ?>&año=<?php echo $año_siguiente; ?>" class="nav-mes">
                        Siguiente →
                    </a>
                </div>

                <div class="calendario-grid">
                    <!-- Headers de días -->
                    <div class="dia-header">Dom</div>
                    <div class="dia-header">Lun</div>
                    <div class="dia-header">Mar</div>
                    <div class="dia-header">Mié</div>
                    <div class="dia-header">Jue</div>
                    <div class="dia-header">Vie</div>
                    <div class="dia-header">Sáb</div>

                    <?php
                    // Días vacíos al inicio
                    for ($i = 0; $i < $dia_semana; $i++) {
                        echo '<div class="dia-celda dia-otro-mes"></div>';
                    }

                    // Días del mes
                    for ($dia = 1; $dia <= $numero_dias; $dia++) {
                        $fecha_completa = sprintf('%04d-%02d-%02d', $año, $mes, $dia);
                        $es_hoy = $fecha_completa == date('Y-m-d');
                        $es_pasado = $fecha_completa < date('Y-m-d');
                        
                        $clases = ['dia-celda'];
                        $onclick = '';
                        
                        // Determinar clases y comportamiento según si es pasado o futuro
                        if ($es_pasado) {
                            $clases[] = 'dia-pasado';
                            // No agregar onclick para días pasados
                        } else {
                            $clases[] = 'dia-disponible';
                            $onclick = 'onclick="irAReserva(\'' . $fecha_completa . '\')"';
                        }
                        
                        if ($es_hoy) $clases[] = 'dia-hoy';
                        
                        // Verificar si hay reservas este día
                        $reservas_dia = isset($reservas_mes[$fecha_completa]) ? $reservas_mes[$fecha_completa] : null;
                        
                        if ($reservas_dia && !$es_pasado) {
                            $clases[] = 'dia-con-reservas';
                            if ($reservas_dia['total_reservas'] >= 5) {
                                $clases[] = 'dia-ocupado';
                            }
                        }
                        
                        echo '<div class="' . implode(' ', $clases) . '" ' . $onclick . '">';
                        echo '<div class="dia-numero">' . $dia . '</div>';
                        
                        // Mostrar indicadores de reservas
                        if ($reservas_dia) {
                            $horarios = explode(', ', $reservas_dia['horarios']);
                            $estados = explode(', ', $reservas_dia['estados']);
                            
                            for ($i = 0; $i < min(3, count($horarios)); $i++) {
                                $estado = $estados[$i];
                                echo '<span class="reserva-indicador reserva-' . $estado . '">';
                                echo htmlspecialchars($horarios[$i]);
                                echo '</span>';
                            }
                            
                            if (count($horarios) > 3) {
                                echo '<span class="reserva-indicador" style="background: #6c757d; color: white;">+' . (count($horarios) - 3) . ' más</span>';
                            }
                        }
                        
                        echo '</div>';
                    }

                    // Días vacíos al final
                    $celdas_usadas = $dia_semana + $numero_dias;
                    $celdas_faltantes = $celdas_usadas % 7 == 0 ? 0 : 7 - ($celdas_usadas % 7);
                    for ($i = 0; $i < $celdas_faltantes; $i++) {
                        echo '<div class="dia-celda dia-otro-mes"></div>';
                    }
                    ?>
                </div>

                <!-- Leyenda -->
                <div class="leyenda">
                    <div class="leyenda-item">
                        <div class="leyenda-color" style="background: #fff3cd; border: 2px solid #ffc107;"></div>
                        <span>Hoy</span>
                    </div>
                    <div class="leyenda-item">
                        <div class="leyenda-color" style="background: #e3f2fd;"></div>
                        <span>Con reservas</span>
                    </div>
                    <div class="leyenda-item">
                        <div class="leyenda-color" style="background: #ffebee;"></div>
                        <span>Muy ocupado</span>
                    </div>
                    <div class="leyenda-item">
                        <div class="leyenda-color" style="background: #fff3cd;"></div>
                        <span>Pendiente</span>
                    </div>
                    <div class="leyenda-item">
                        <div class="leyenda-color" style="background: #d4edda;"></div>
                        <span>Aprobada</span>
                    </div>
                    <div class="leyenda-item">
                        <div class="leyenda-color" style="background: #d1ecf1;"></div>
                        <span>Ejecutada</span>
                    </div>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="alert alert-info mt-3">
                <strong>Información:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <li>Haz clic en cualquier día disponible (futuro) para crear una nueva reserva</li>
                    <li>Los días muy ocupados (5+ reservas) se muestran en rojo</li>
                    <li>Puedes ver el detalle de cada reserva en "Mis Reservas"</li>
                </ul>
            </div>

            <!-- Botones de acción -->
            <div class="card mt-4">
                <div class="card-body text-center">
                    <h3>¿Necesitas hacer una reserva?</h3>
                    <p>Haz clic en una fecha disponible del calendario o usa el botón de abajo para crear una nueva reserva.</p>
                    <a href="nueva_reserva.php" class="btn btn-primary">➕ Nueva Reserva de Bombeo</a>
                    <a href="mis_reservas.php" class="btn btn-secondary">📋 Ver Mis Reservas</a>
                </div>
            </div>
        </div>
    </main>

    <script>
        function irAReserva(fecha) {
            // Verificar si la fecha no es pasada (doble verificación en JavaScript)
            const fechaSeleccionada = new Date(fecha);
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            
            if (fechaSeleccionada < hoy) {
                alert('⚠️ No puedes hacer reservas en fechas pasadas.');
                return;
            }
            
            // Redirigir a nueva reserva con la fecha preseleccionada
            window.location.href = 'nueva_reserva.php?fecha=' + fecha;
        }

        // Función para mostrar/ocultar sidebar en móviles
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }

        // Prevenir clicks accidentales en días pasados (opcional)
        document.addEventListener('DOMContentLoaded', function() {
            const diasPasados = document.querySelectorAll('.dia-pasado');
            diasPasados.forEach(dia => {
                dia.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });
        });
    </script>
</body>
</html>