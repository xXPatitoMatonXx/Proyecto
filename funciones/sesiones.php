<?php
/**
 * Funciones para manejo de sesiones
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

/**
 * Verificar si el usuario está logueado
 */
function verificar_sesion() {
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    return true;
}

/**
 * Verificar si el usuario es administrador
 */
function es_admin() {
    return isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == ROL_ADMIN;
}

/**
 * Verificar si el usuario es usuario regular
 */
function es_usuario() {
    return isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == ROL_USUARIO;
}

/**
 * Redirigir si no está logueado
 */
function requerir_login() {
    if (!verificar_sesion()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
}

/**
 * Redirigir si no es administrador
 */
function requerir_admin() {
    requerir_login();
    if (!es_admin()) {
        header('Location: ' . BASE_URL . 'paginas/usuario/inicio.php');
        exit();
    }
}

/**
 * Cerrar sesión
 */
function cerrar_sesion() {
    session_start();
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

/**
 * Obtener información del usuario actual
 */
function obtener_usuario_actual() {
    if (verificar_sesion()) {
        return [
            'id' => $_SESSION['usuario_id'],
            'username' => $_SESSION['username'],
            'nombre' => $_SESSION['nombre'],
            'rol' => $_SESSION['id_rol']
        ];
    }
    return null;
}

/**
 * Generar token CSRF para formularios
 */
function generar_token_csrf() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verificar_token_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>