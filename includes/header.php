<?php
/**
 * Header común para todas las páginas - Diseño Profesional
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

if (!isset($_SESSION)) {
    session_start();
}

require_once dirname(__FILE__) . '/../funciones/sesiones.php';

// Verificar que el usuario esté logueado
if (!verificar_sesion()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$usuario = obtener_usuario_actual();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo_pagina) ? $titulo_pagina . ' - ' : ''; ?>Sistema GHC</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/estilos.css">
    
    <?php if (isset($css_adicional)): ?>
        <?php foreach ($css_adicional as $css): ?>
            <link rel="stylesheet" href="<?php echo BASE_URL . $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%231e3a8a'%3E%3Cpath d='M12 2L13.09 8.26L22 9L13.09 9.74L12 16L10.91 9.74L2 9L10.91 8.26L12 2Z'/%3E%3C/svg%3E">
</head>
<body>
    <!-- Header Principal -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">
                    <i class="bi bi-droplet-fill"></i>
                </div>
                <div>
                    <h1>Sistema GHC</h1>
                    <small style="opacity: 0.8; font-size: 0.8rem;">Gestión Hídrica Comunitaria</small>
                </div>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                    <div class="user-role">
                        <?php echo es_admin() ? 'Administrador' : 'Usuario'; ?>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-secondary btn-small">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="d-none d-md-inline">Cerrar Sesión</span>
                </a>
            </div>
        </div>
    </header>
    
    <!-- Botón para colapsar/expandir sidebar -->
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()" title="Mostrar/Ocultar menú">
        <i class="bi bi-list" id="toggleIcon"></i>
    </button>