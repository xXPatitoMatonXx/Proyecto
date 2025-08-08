<?php
/**
 * Archivo de conexión a la base de datos
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

// Configuración de la base de datos
$servidor = "localhost";
$usuario_db = "root";
$contraseña_db = "";
$nombre_db = "ghc_db";

// Crear conexión
$conexion = new mysqli($servidor, $usuario_db, $contraseña_db, $nombre_db);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Establecer codificación UTF-8
$conexion->set_charset("utf8mb4");

// Función para cerrar conexión (opcional)
function cerrar_conexion() {
    global $conexion;
    if ($conexion) {
        $conexion->close();
    }
}
?>