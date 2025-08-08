<?php
/**
 * Sistema de Gestión Hídrica Comunitaria (GHC) - Diseño Profesional
 * Página principal de acceso
 */
session_start();
require_once 'config/conexion.php';
require_once 'config/config.php';
require_once 'funciones/sesiones.php';

// Si ya está logueado, redirigir al área correspondiente
if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['id_rol'] == 1) {
        header('Location: paginas/admin/inicio_admin.php');
    } else {
        header('Location: paginas/usuario/inicio.php');
    }
    exit();
}

// Inicializar variable de error
$error = '';
$loading = false;

// Procesar login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'login') {
    $loading = true;
    
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (!empty($username) && !empty($password)) {
            $query = "SELECT * FROM usuarios WHERE username = ? AND activo = 1";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows == 1) {
                $usuario = $resultado->fetch_assoc();
                if (password_verify($password, $usuario['contraseña'])) {
                    $_SESSION['usuario_id'] = $usuario['id_usuario'];
                    $_SESSION['username'] = $usuario['username'];
                    $_SESSION['nombre'] = $usuario['nombre_completo'];
                    $_SESSION['id_rol'] = $usuario['id_rol'];
                    
                    if ($usuario['id_rol'] == 1) {
                        header('Location: paginas/admin/inicio_admin.php');
                    } else {
                        header('Location: paginas/usuario/inicio.php');
                    }
                    exit();
                } else {
                    $error = "Contraseña incorrecta";
                }
            } else {
                $error = "Usuario no encontrado o inactivo";
            }
            
            $stmt->close();
        } else {
            $error = "Por favor, complete todos los campos";
        }
    } else {
        $error = "Datos de formulario incompletos";
    }
    $loading = false;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GHC - Sistema de Gestión Hídrica</title>
    <meta name="description" content="Sistema de Gestión Hídrica Comunitaria para Cañada de Flores, Hidalgo">
    <meta name="keywords" content="agua, pozo, comunitario, gestión, bombeo, Hidalgo">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/login.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%231e3a8a'%3E%3Cpath d='M12 2L13.09 8.26L22 9L13.09 9.74L12 16L10.91 9.74L2 9L10.91 8.26L12 2Z'/%3E%3C/svg%3E">
</head>
<body class="login-body">
    
    <!-- Overlay de carga -->
    <?php if ($loading): ?>
    <div class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>
    <?php endif; ?>
    
    <div class="login-container">
        
        <!-- Formulario de Login -->
        <div class="login-box">
            <div class="login-header">
                <div class="logo-section">
                    <div class="logo-icon logo-icon-large">
                        <i class="bi bi-droplet-fill"></i>
                    </div>
                    <div>
                        <h1>Gestión Hídrica Comunitaria</h1>
                        <p>Cañada de Flores, Hidalgo</p>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <strong>Error de acceso:</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form" id="loginForm">
                <input type="hidden" name="accion" value="login">
                
                <div class="input-group">
                    <label for="username" class="required">Usuario</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           required
                           placeholder="Ingresa tu nombre de usuario"
                           autocomplete="username">
                    <small>Ingresa tu nombre de usuario</small>
                </div>
                
                <div class="input-group">
                    <label for="password" class="required">Contraseña</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           placeholder="Ingresa tu contraseña"
                           autocomplete="current-password">
                    <small>Ingresa tu contraseña</small>
                </div>
                
                <button type="submit" class="btn-login" id="submitBtn">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Ingresar</span>
                </button>
            </form>
            
            <div class="login-footer">
                <p>
                    <a href="paginas/registro.php">
                        <i class="bi bi-person-plus"></i>
                        ¿No tienes cuenta? Regístrate aquí
                    </a>
                </p>
                <small>
                    <i class="bi bi-shield-check"></i>
                    Versión 1.0 - Gestión Hídrica Comunitaria
                </small>
            </div>
        </div>
        
        <!-- Panel Informativo -->
        <div class="login-bg-info">
            <div class="info-card">
                <h3>Sistema de Gestión Hídrica</h3>
                <p>Plataforma diseñada para la administración eficiente del pozo comunitario, facilitando la reserva de horarios y el monitoreo del consumo de agua.</p>
                
                <ul>
                    <li>
                        <i class="bi bi-calendar-check"></i>
                        Reserva de horarios de bombeo
                    </li>
                    <li>
                        <i class="bi bi-graph-up"></i>
                        Monitoreo de consumo en tiempo real
                    </li>
                    <li>
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        Reportes detallados de uso
                    </li>
                    <li>
                        <i class="bi bi-people"></i>
                        Gestión comunitaria transparente
                    </li>
                    <li>
                        <i class="bi bi-shield-lock"></i>
                        Acceso seguro y controlado
                    </li>
                </ul>
                
                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.2);">
                    <p style="font-size: 0.9rem; opacity: 0.8;">
                        <i class="bi bi-geo-alt"></i>
                        Desarrollado para la comunidad ejidal de Cañada de Flores
                    </p>
                </div>
            </div>
        </div>
        
    </div>

    <script>
    // Funcionalidad del formulario de login
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        
        // Validación en tiempo real
        function validateField(field) {
            const value = field.value.trim();
            const isValid = value.length >= 3;
            
            field.classList.remove('valid', 'invalid');
            field.classList.add(isValid ? 'valid' : 'invalid');
            
            return isValid;
        }
        
        usernameInput.addEventListener('input', function() {
            validateField(this);
            updateSubmitButton();
        });
        
        passwordInput.addEventListener('input', function() {
            validateField(this);
            updateSubmitButton();
        });
        
        function updateSubmitButton() {
            const isFormValid = usernameInput.value.trim().length >= 3 && 
                               passwordInput.value.length >= 3;
            submitBtn.disabled = !isFormValid;
        }
        
        // Manejo del envío del formulario
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateField(usernameInput) || !validateField(passwordInput)) {
                showMessage('error', 'Por favor, completa todos los campos correctamente.');
                return;
            }
            
            // Mostrar estado de carga
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i> Verificando...';
            
            // Enviar formulario después de un breve delay para mostrar el loading
            setTimeout(() => {
                form.submit();
            }, 500);
        });
        
        // Función para mostrar mensajes
        function showMessage(type, message) {
            const existingAlert = document.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <i class="bi bi-${type === 'error' ? 'exclamation-triangle-fill' : 'check-circle-fill'}"></i>
                <div><strong>${type === 'error' ? 'Error:' : 'Éxito:'}</strong> ${message}</div>
            `;
            
            form.insertBefore(alert, form.firstChild);
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }
            }, 5000);
        }
        
        // Enfocar el primer campo
        usernameInput.focus();
        
        // Inicializar estado del botón
        updateSubmitButton();
    });
    
    // Prevenir envío múltiple del formulario
    let formSubmitted = false;
    document.getElementById('loginForm').addEventListener('submit', function() {
        if (formSubmitted) {
            return false;
        }
        formSubmitted = true;
    });
    
    // Animación de entrada
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.login-container');
        container.style.opacity = '0';
        container.style.transform = 'scale(0.95)';
        
        setTimeout(() => {
            container.style.transition = 'all 0.5s ease-out';
            container.style.opacity = '1';
            container.style.transform = 'scale(1)';
        }, 100);
    });
    </script>
    
    <!-- Estilos adicionales para animaciones y el icono más grande -->
    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Estilo para hacer el icono de la gota más grande */
        .logo-icon-large {
            width: 80px !important;
            height: 80px !important;
            font-size: 2.5rem !important;
        }
        
        /* Ajuste adicional si necesitas que sea aún más grande */
        .logo-icon-large i {
            font-size: 2.5rem !important;
        }
    </style>
    
</body>
</html>