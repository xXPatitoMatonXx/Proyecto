<?php
/**
 * Funciones para generación de reportes
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

require_once '../config/conexion.php';

/**
 * Obtener datos de bombeo de un usuario en un mes específico
 */
function obtener_datos_bombeo_usuario($usuario_id, $mes, $año, $conexion) {
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

/**
 * Obtener resumen de bombeo de un usuario en un mes
 */
function obtener_resumen_usuario($usuario_id, $mes, $año, $conexion) {
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
        $usuario = $stmt_usuario->get_result()->fetch_assoc();
        
        $resultado = [
            'total_sesiones' => 0,
            'total_horas' => 0,
            'total_litros' => 0,
            'nombre_completo' => $usuario['nombre_completo'],
            'rfc' => $usuario['rfc']
        ];
    }
    
    return $resultado;
}

/**
 * Obtener datos de todos los usuarios en un mes específico
 */
function obtener_datos_todos_usuarios($mes, $año, $conexion) {
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
    $stmt->bind_param("ii", $mes, $año);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Generar reporte individual en HTML (para PDF)
 */
function generar_reporte_individual_html($usuario_id, $mes, $año, $conexion) {
    $datos = obtener_datos_bombeo_usuario($usuario_id, $mes, $año, $conexion);
    $resumen = obtener_resumen_usuario($usuario_id, $mes, $año, $conexion);
    
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    $html = '
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .info-usuario { margin-bottom: 20px; background: #f5f5f5; padding: 10px; }
        .resumen { margin-bottom: 20px; background: #e8f4f8; padding: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .total-row { background-color: #f2f2f2; font-weight: bold; }
        .no-data { text-align: center; padding: 20px; color: #666; }
    </style>
    
    <div class="header">
        <h1>Sistema de Gestión Hídrica Comunitaria</h1>
        <h2>Reporte Individual de Bombeo</h2>
        <h3>' . $meses[$mes] . ' ' . $año . '</h3>
    </div>
    
    <div class="info-usuario">
        <strong>Usuario:</strong> ' . htmlspecialchars($resumen['nombre_completo']) . '<br>
        <strong>RFC:</strong> ' . htmlspecialchars($resumen['rfc']) . '<br>
        <strong>Fecha de generación:</strong> ' . date('d/m/Y H:i:s') . '
    </div>
    
    <div class="resumen">
        <h3>Resumen del Mes</h3>
        <strong>Total de sesiones de bombeo:</strong> ' . $resumen['total_sesiones'] . '<br>
        <strong>Total de horas bombeadas:</strong> ' . number_format($resumen['total_horas'], 2) . ' hrs<br>
        <strong>Total de litros bombeados:</strong> ' . number_format($resumen['total_litros']) . ' litros
    </div>';
    
    if (count($datos) > 0) {
        $html .= '
        <table>
            <tr>
                <th>Fecha</th>
                <th>Hora Inicio</th>
                <th>Hora Fin</th>
                <th>Horas Bombeadas</th>
                <th>Litros Bombeados</th>
                <th>Observaciones</th>
            </tr>';
        
        foreach ($datos as $registro) {
            $html .= '
            <tr>
                <td>' . date('d/m/Y', strtotime($registro['fecha_bombeo'])) . '</td>
                <td>' . $registro['hora_inicio'] . '</td>
                <td>' . $registro['hora_fin'] . '</td>
                <td>' . number_format($registro['horas_bombeadas'], 2) . '</td>
                <td>' . number_format($registro['litros_bombeados']) . '</td>
                <td>' . htmlspecialchars($registro['observaciones'] ?? '') . '</td>
            </tr>';
        }
        
        $html .= '</table>';
    } else {
        $html .= '<div class="no-data">No hay registros de bombeo para este período.</div>';
    }
    
    return $html;
}

/**
 * Generar reporte general en HTML (para PDF)
 */
function generar_reporte_general_html($mes, $año, $conexion) {
    $datos = obtener_datos_todos_usuarios($mes, $año, $conexion);
    
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    // Calcular totales generales
    $total_sesiones = array_sum(array_column($datos, 'total_sesiones'));
    $total_horas_general = array_sum(array_column($datos, 'total_horas'));
    $total_litros_general = array_sum(array_column($datos, 'total_litros'));
    
    $html = '
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .resumen-general { margin-bottom: 20px; background: #e8f4f8; padding: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .total-row { background-color: #f2f2f2; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
    
    <div class="header">
        <h1>Sistema de Gestión Hídrica Comunitaria</h1>
        <h2>Reporte General de Bombeo</h2>
        <h3>' . $meses[$mes] . ' ' . $año . '</h3>
        <p>Fecha de generación: ' . date('d/m/Y H:i:s') . '</p>
    </div>
    
    <div class="resumen-general">
        <h3>Resumen General del Mes</h3>
        <strong>Total de usuarios activos:</strong> ' . count($datos) . '<br>
        <strong>Total de sesiones de bombeo:</strong> ' . $total_sesiones . '<br>
        <strong>Total de horas bombeadas:</strong> ' . number_format($total_horas_general, 2) . ' hrs<br>
        <strong>Total de litros bombeados:</strong> ' . number_format($total_litros_general) . ' litros
    </div>
    
    <table>
        <tr>
            <th>Usuario</th>
            <th>RFC</th>
            <th class="text-center">Sesiones</th>
            <th class="text-center">Horas</th>
            <th class="text-center">Litros</th>
        </tr>';
    
    foreach ($datos as $usuario) {
        $html .= '
        <tr>
            <td>' . htmlspecialchars($usuario['nombre_completo']) . '</td>
            <td>' . htmlspecialchars($usuario['rfc']) . '</td>
            <td class="text-center">' . $usuario['total_sesiones'] . '</td>
            <td class="text-center">' . number_format($usuario['total_horas'], 2) . '</td>
            <td class="text-right">' . number_format($usuario['total_litros']) . '</td>
        </tr>';
    }
    
    $html .= '
        <tr class="total-row">
            <td colspan="2"><strong>TOTALES</strong></td>
            <td class="text-center"><strong>' . $total_sesiones . '</strong></td>
            <td class="text-center"><strong>' . number_format($total_horas_general, 2) . '</strong></td>
            <td class="text-right"><strong>' . number_format($total_litros_general) . '</strong></td>
        </tr>
    </table>';
    
    return $html;
}

/**
 * Guardar reporte en tabla de reportes mensuales
 */
function guardar_reporte_mensual($usuario_id, $mes, $año, $total_horas, $total_litros, $archivo_pdf, $conexion) {
    // Verificar si ya existe un reporte para este usuario/mes/año
    $query_check = "SELECT id_reporte FROM reportes_mensuales WHERE id_usuario = ? AND mes = ? AND año = ?";
    $stmt_check = $conexion->prepare($query_check);
    $stmt_check->bind_param("iii", $usuario_id, $mes, $año);
    $stmt_check->execute();
    $existe = $stmt_check->get_result()->num_rows > 0;
    
    if ($existe) {
        // Actualizar reporte existente
        $query = "UPDATE reportes_mensuales SET 
                total_horas = ?, 
                total_litros = ?, 
                archivo_pdf = ?, 
                fecha_generacion = CURRENT_TIMESTAMP 
                WHERE id_usuario = ? AND mes = ? AND año = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("dislii", $total_horas, $total_litros, $archivo_pdf, $usuario_id, $mes, $año);
    } else {
        // Crear nuevo reporte
        $query = "INSERT INTO reportes_mensuales (id_usuario, mes, año, total_horas, total_litros, archivo_pdf) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("iiidis", $usuario_id, $mes, $año, $total_horas, $total_litros, $archivo_pdf);
    }
    
    return $stmt->execute();
}
?>