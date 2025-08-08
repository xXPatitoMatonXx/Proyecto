<?php
/**
 * Archivo de configuración general
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

// Configuración de la zona horaria
date_default_timezone_set('America/Mexico_City');

// URL base del proyecto (ajustar según tu configuración de XAMPP)
define('BASE_URL', 'http://localhost/GHC/');

// Rutas del sistema
define('ROOT_PATH', __DIR__ . '/../');
define('UPLOADS_PATH', ROOT_PATH . 'reportes/');

// Configuración de errores (para desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración para reportes PDF
define('LITROS_POR_HORA', 52000);

// Configuración de roles
define('ROL_ADMIN', 1);
define('ROL_USUARIO', 2);

// Horarios de bombeo disponibles
$horarios_bombeo = [
    '06:00:00' => '08:00:00',
    '08:00:00' => '10:00:00', 
    '10:00:00' => '12:00:00',
    '14:00:00' => '16:00:00',
    '16:00:00' => '18:00:00'
];

// Mensajes del sistema
$mensajes = [
    'login_exitoso' => 'Inicio de sesión exitoso',
    'login_error' => 'Usuario o contraseña incorrectos',
    'acceso_denegado' => 'No tienes permisos para acceder a esta página',
    'sesion_expirada' => 'Tu sesión ha expirado, inicia sesión nuevamente',
    'registro_exitoso' => 'Usuario registrado correctamente',
    'error_general' => 'Ha ocurrido un error, intenta nuevamente'
];
?>