<?php
/**
 * Navegación lateral común - Diseño Profesional
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

// Obtener la página actual para marcar el enlace activo
$pagina_actual = basename($_SERVER['PHP_SELF']);
$ruta_base = es_admin() ? BASE_URL . 'paginas/admin/' : BASE_URL . 'paginas/usuario/';

function enlace_activo($pagina) {
    global $pagina_actual;
    return $pagina_actual === $pagina ? 'active' : '';
}
?>

<!-- Sidebar de Navegación -->
<nav class="sidebar" id="sidebar">
    <ul class="nav-menu">
        
        <?php if (es_admin()): ?>
            <!-- Menú para Administrador -->
            <li class="nav-item">
                <a href="<?php echo $ruta_base; ?>inicio_admin.php" class="nav-link <?php echo enlace_activo('inicio_admin.php'); ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span class="nav-text">Panel Administrativo</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $ruta_base; ?>usuarios.php" class="nav-link <?php echo enlace_activo('usuarios.php'); ?>">
                    <i class="bi bi-people"></i>
                    <span class="nav-text">Gestión de Usuarios</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $ruta_base; ?>solicitudes.php" class="nav-link <?php echo enlace_activo('solicitudes.php'); ?>">
                    <i class="bi bi-clipboard-check"></i>
                    <span class="nav-text">Solicitudes de Bombeo</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $ruta_base; ?>registro_bombeo.php" class="nav-link <?php echo enlace_activo('registro_bombeo.php'); ?>">
                    <i class="bi bi-gear-fill"></i>
                    <span class="nav-text">Registro de Bombeo</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $ruta_base; ?>reportes.php" class="nav-link <?php echo enlace_activo('reportes.php'); ?>">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span class="nav-text">Reportes Generales</span>
                </a>
            </li>
            
        <?php else: ?>
            <!-- Menú para Usuario Regular -->
            <li class="nav-item">
                <a href="<?php echo $ruta_base; ?>inicio.php" class="nav-link <?php echo enlace_activo('inicio.php'); ?>">
                    <i class="bi bi-house-door"></i>
                    <span class="nav-text">Panel Principal</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $ruta_base; ?>calendario.php" class="nav-link <?php echo enlace_activo('calendario.php'); ?>">
                    <i class="bi bi-calendar3"></i>
                    <span class="nav-text">Calendario de Reservas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $ruta_base; ?>nueva_reserva.php" class="nav-link <?php echo enlace_activo('nueva_reserva.php'); ?>">
                    <i class="bi bi-plus-circle"></i>
                    <span class="nav-text">Nueva Reserva</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $ruta_base; ?>mis_reservas.php" class="nav-link <?php echo enlace_activo('mis_reservas.php'); ?>">
                    <i class="bi bi-clipboard-data"></i>
                    <span class="nav-text">Mis Reservas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $ruta_base; ?>mi_reporte.php" class="nav-link <?php echo enlace_activo('mi_reporte.php'); ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span class="nav-text">Mi Reporte</span>
                </a>
            </li>
            
        <?php endif; ?>
        
        <!-- Separador -->
        <li class="nav-item" style="border-top: 1px solid var(--border-color); margin-top: 2rem; padding-top: 1rem;">
            <a href="<?php echo BASE_URL; ?>logout.php" class="nav-link" style="color: var(--danger-color);">
                <i class="bi bi-box-arrow-left"></i>
                <span class="nav-text">Cerrar Sesión</span>
            </a>
        </li>
    </ul>
</nav>

<script>
// Funcionalidad del sidebar colapsable
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const toggleIcon = document.getElementById('toggleIcon');
    
    sidebar.classList.toggle('collapsed');
    
    if (mainContent) {
        mainContent.classList.toggle('collapsed');
    }
    
    // Cambiar icono
    if (sidebar.classList.contains('collapsed')) {
        toggleIcon.className = 'bi bi-list';
        localStorage.setItem('sidebarCollapsed', 'true');
    } else {
        toggleIcon.className = 'bi bi-x-lg';
        localStorage.setItem('sidebarCollapsed', 'false');
    }
}

// Restaurar estado del sidebar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (isCollapsed) {
        sidebar.classList.add('collapsed');
        if (mainContent) {
            mainContent.classList.add('collapsed');
        }
        toggleIcon.className = 'bi bi-list';
    } else {
        toggleIcon.className = 'bi bi-x-lg';
    }
});

// Cerrar sidebar en móviles al hacer clic fuera
document.addEventListener('click', function(event) {
    if (window.innerWidth <= 1024) {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        
        if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    }
});

// Abrir sidebar en móviles
function toggleSidebarMobile() {
    if (window.innerWidth <= 1024) {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('active');
    } else {
        toggleSidebar();
    }
}

// Actualizar comportamiento según el tamaño de pantalla
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (window.innerWidth <= 1024) {
        // En móviles, quitar clases de colapso y usar active
        sidebar.classList.remove('collapsed');
        if (mainContent) {
            mainContent.classList.remove('collapsed');
        }
    } else {
        // En escritorio, restaurar estado guardado
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            if (mainContent) {
                mainContent.classList.add('collapsed');
            }
        }
        sidebar.classList.remove('active');
    }
});

// Cambiar función del botón según el tamaño de pantalla
document.getElementById('sidebarToggle').onclick = function() {
    if (window.innerWidth <= 1024) {
        toggleSidebarMobile();
    } else {
        toggleSidebar();
    }
};
</script>