<?php
/**
 * Funciones de validación
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

/**
 * Validar RFC mexicano
 */
function validar_rfc($rfc) {
    $rfc = strtoupper(trim($rfc));
    // Patrón para RFC de persona física (13 caracteres)
    $patron = '/^[A-Z&Ñ]{4}[0-9]{6}[A-Z0-9]{3}$/';
    return preg_match($patron, $rfc);
}

/**
 * Validar teléfono
 */
function validar_telefono($telefono) {
    $telefono = preg_replace('/[^0-9]/', '', $telefono);
    return strlen($telefono) >= 10 && strlen($telefono) <= 15;
}

/**
 * Validar email
 */
function validar_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar contraseña
 */
function validar_contraseña($contraseña) {
    // Mínimo 6 caracteres
    if (strlen($contraseña) < 6) {
        return false;
    }
    return true;
}

/**
 * Validar username
 */
function validar_username($username) {
    // Solo letras, números y guiones bajos, mínimo 3 caracteres
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

/**
 * Limpiar datos de entrada
 */
function limpiar_entrada($datos) {
    $datos = trim($datos);
    $datos = stripslashes($datos);
    $datos = htmlspecialchars($datos);
    return $datos;
}

/**
 * Verificar si RFC existe en socios autorizados
 */
function verificar_rfc_autorizado($rfc, $conexion) {
    $query = "SELECT id_socio FROM socios_autorizados WHERE rfc = ? AND activo = 1";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("s", $rfc);
    $stmt->execute();
    $resultado = $stmt->get_result();
    return $resultado->num_rows > 0;
}

/**
 * Verificar si username ya existe
 */
function verificar_username_existe($username, $conexion) {
    $query = "SELECT id_usuario FROM usuarios WHERE username = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $resultado = $stmt->get_result();
    return $resultado->num_rows > 0;
}

/**
 * Validar hora en formato HH:MM:SS
 */
function validar_hora($hora) {
    return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $hora);
}

/**
 * Validar fecha en formato YYYY-MM-DD
 */
function validar_fecha($fecha) {
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d && $d->format('Y-m-d') === $fecha;
}

/**
 * Verificar que la fecha no sea pasada
 */
function validar_fecha_futura($fecha) {
    $fecha_obj = new DateTime($fecha);
    $hoy = new DateTime();
    return $fecha_obj >= $hoy;
}
?>