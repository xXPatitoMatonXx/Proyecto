<?php
/**
 * Página de Registro - Diseño Profesional
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

session_start();
require_once '../config/conexion.php';
require_once '../config/config.php';
require_once '../funciones/sesiones.php';
require_once '../funciones/validaciones.php';

// Si ya está logueado, redirigir
if (verificar_sesion()) {
    if (es_admin()) {
        header('Location: admin/inicio_admin.php');
    } else {
        header('Location: usuario/inicio.php');
    }
    exit();
}

$errores = [];
$exito = '';

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'registro') {
    // Recoger y limpiar datos
    $username = limpiar_entrada($_POST['username']);
    $nombre_completo = limpiar_entrada($_POST['nombre_completo']);
    $domicilio = limpiar_entrada($_POST['domicilio']);
    $rfc = limpiar_entrada(strtoupper($_POST['rfc']));
    $telefono = limpiar_entrada($_POST['telefono']);
    $password = $_POST['password'];
    $confirmar_password = $_POST['confirmar_password'];
    
    // Validaciones
    if (empty($username)) {
        $errores[] = 'El nombre de usuario es requerido';
    } elseif (!validar_username($username)) {
        $errores[] = 'El nombre de usuario solo puede contener letras, números y guiones bajos (3-20 caracteres)';
    } elseif (verificar_username_existe($username, $conexion)) {
        $errores[] = 'El nombre de usuario ya está en uso';
    }
    
    if (empty($nombre_completo)) {
        $errores[] = 'El nombre completo es requerido';
    }
    
    if (empty($rfc)) {
        $errores[] = 'El RFC es requerido';
    } elseif (!validar_rfc($rfc)) {
        $errores[] = 'El RFC no tiene un formato válido';
    } elseif (!verificar_rfc_autorizado($rfc, $conexion)) {
        $errores[] = 'El RFC no está autorizado para registrarse en el sistema';
    }
    
    if (!empty($telefono) && !validar_telefono($telefono)) {
        $errores[] = 'El teléfono no tiene un formato válido';
    }
    
    if (empty($password)) {
        $errores[] = 'La contraseña es requerida';
    } elseif (!validar_contraseña($password)) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres';
    }
    
    if ($password !== $confirmar_password) {
        $errores[] = 'Las contraseñas no coinciden';
    }
    
    // Si no hay errores, proceder con el registro
    if (empty($errores)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO usuarios (username, nombre_completo, domicilio, rfc, telefono, contraseña, id_rol) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($query);
        $rol_usuario = ROL_USUARIO;
        $stmt->bind_param("ssssssi", $username, $nombre_completo, $domicilio, $rfc, $telefono, $password_hash, $rol_usuario);
        
        if ($stmt->execute()) {
            $exito = 'Usuario registrado correctamente. Ya puedes iniciar sesión.';
            $username = $nombre_completo = $domicilio = $rfc = $telefono = '';
        } else {
            $errores[] = 'Error al registrar el usuario. Intenta nuevamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GHC - Registro de Usuario</title>
    <meta name="description" content="Registro de nuevo usuario en el Sistema de Gestión Hídrica Comunitaria">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="../css/login.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%231e3a8a'%3E%3Cpath d='M12 2L13.09 8.26L22 9L13.09 9.74L12 16L10.91 9.74L2 9L10.91 8.26L12 2Z'/%3E%3C/svg%3E">
</head>
<body class="login-body">
    <div class="single-column-container">
        
        <!-- Formulario de Registro Centrado -->
        <div class="centered-login-box">
            <div class="login-header">
                <div class="logo-section">
                    <div class="logo-icon">
                        <i class="bi bi-droplet-fill"></i>
                    </div>
                    <div>
                        <h1>Sistema GHC</h1>
                        <h2>Registro de Usuario</h2>
                        <p>Cañada de Flores, Hidalgo</p>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($errores)): ?>
                <div class="alert alert-error">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <strong>Errores encontrados:</strong>
                        <ul>
                            <?php foreach ($errores as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($exito)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <div>
                        <strong>¡Registro exitoso!</strong> <?php echo htmlspecialchars($exito); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form" id="registroForm">
                <input type="hidden" name="accion" value="registro">
                
                <div class="input-group">
                    <label for="username" class="required">Nombre de Usuario</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           required 
                           value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"
                           placeholder="Ej: juan_lopez"
                           autocomplete="username">
                    <small>Solo letras, números y guiones bajos (3-20 caracteres)</small>
                </div>
                
                <div class="input-group">
                    <label for="nombre_completo" class="required">Nombre Completo</label>
                    <input type="text" 
                           id="nombre_completo" 
                           name="nombre_completo" 
                           required 
                           value="<?php echo isset($nombre_completo) ? htmlspecialchars($nombre_completo) : ''; ?>"
                           placeholder="Ej: Juan López García"
                           autocomplete="name">
                </div>
                
                <div class="input-group">
                    <label for="rfc" class="required">RFC</label>
                    <input type="text" 
                           id="rfc" 
                           name="rfc" 
                           required 
                           maxlength="13"
                           value="<?php echo isset($rfc) ? htmlspecialchars($rfc) : ''; ?>"
                           placeholder="Ej: LOPJ850315A1B"
                           style="text-transform: uppercase;"
                           autocomplete="off">
                    <small>Debe estar autorizado por el administrador</small>
                </div>
                
                <div class="input-group">
                    <label for="domicilio">Domicilio</label>
                    <textarea id="domicilio" 
                              name="domicilio" 
                              rows="2"
                              placeholder="Ej: Calle Principal #123, Cañada de Flores"
                              autocomplete="street-address"><?php echo isset($domicilio) ? htmlspecialchars($domicilio) : ''; ?></textarea>
                </div>
                
                <div class="input-group">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" 
                           id="telefono" 
                           name="telefono" 
                           value="<?php echo isset($telefono) ? htmlspecialchars($telefono) : ''; ?>"
                           placeholder="Ej: 7712345678"
                           autocomplete="tel">
                </div>
                
                <div class="input-group">
                    <label for="password" class="required">Contraseña</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           placeholder="Mínimo 6 caracteres"
                           autocomplete="new-password">
                </div>
                
                <div class="input-group">
                    <label for="confirmar_password" class="required">Confirmar Contraseña</label>
                    <input type="password" 
                           id="confirmar_password" 
                           name="confirmar_password" 
                           required 
                           placeholder="Repite tu contraseña"
                           autocomplete="new-password">
                </div>
                
                <button type="submit" class="btn-login" id="submitBtn">
                    <i class="bi bi-person-plus"></i>
                    <span>Registrarse</span>
                </button>
            </form>
            
            <div class="login-footer">
                <p>
                    <a href="../index.php">
                        <i class="bi bi-box-arrow-in-left"></i>
                        ¿Ya tienes cuenta? Inicia sesión aquí
                    </a>
                </p>
                <small>
                    <i class="bi bi-asterisk"></i>
                    Campos obligatorios marcados con *
                </small>
            </div>
        </div>
        
        <!-- Caja de requisitos debajo del formulario -->
        <div class="requisitos-info-abajo">
            <div class="requisitos-header">
                <i class="bi bi-info-circle"></i>
                <span>Requisitos para crear cuenta</span>
            </div>
            <div class="requisitos-lista-compacta">
                <span class="requisito-item-pequeno">
                    <i class="bi bi-check2"></i> Ser socio autorizado del pozo
                </span>
                <span class="requisito-item-pequeno">
                    <i class="bi bi-check2"></i> Tener RFC válido y autorizado
                </span>
                <span class="requisito-item-pequeno">
                    <i class="bi bi-check2"></i> Completar información básica
                </span>
            </div>
            <p class="contacto-admin">
                <i class="bi bi-telephone"></i>
                Contacta al administrador si necesitas autorización
            </p>
        </div>
        
    </div>

    <!-- CSS específico para el diseño de una sola columna -->
    <style>
        /* Redefinir el body y contenedor principal */
        .login-body {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 25%, #3b82f6 75%, #60a5fa 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            overflow-y: auto;
        }
        
        /* Contenedor principal de una sola columna */
        .single-column-container {
            width: 100%;
            max-width: 500px;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            align-items: center;
            margin: 2rem 0;
            padding-bottom: 2rem;
        }
        
        /* Caja del formulario centrada */
        .centered-login-box {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
            text-align: center;
        }
        
        .login-header {
            margin-bottom: 2rem;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
        }
        
        .logo-section h1 {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        
        .logo-section h2 {
            color: #e2e8f0;
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0.2rem 0;
        }
        
        .logo-section p {
            color: #94a3b8;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .login-form {
            width: 100%;
            text-align: left;
        }
        
        .login-footer {
            margin-top: 2rem;
            text-align: center;
        }
        
        .login-footer a {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .login-footer a:hover {
            color: #93c5fd;
        }
        
        .login-footer small {
            color: #94a3b8;
            font-size: 0.8rem;
            margin-top: 1rem;
            display: block;
        }
        
        /* Caja de requisitos debajo del formulario */
        .requisitos-info-abajo {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            width: 100%;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .requisitos-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .requisitos-header i {
            font-size: 1.3rem;
            opacity: 0.9;
        }
        
        .requisitos-lista-compacta {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .requisito-item-pequeno {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            color: white;
            font-size: 0.95rem;
            opacity: 0.95;
            font-weight: 500;
        }
        
        .requisito-item-pequeno i {
            font-size: 1rem;
            color: #22c55e;
            font-weight: bold;
            background: rgba(34, 197, 94, 0.2);
            border-radius: 50%;
            padding: 2px;
            min-width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .contacto-admin {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            color: white;
            font-size: 0.85rem;
            opacity: 0.9;
            margin: 0;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
            font-weight: 500;
        }
        
        .contacto-admin i {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        /* Estilos para los campos del formulario */
        .input-group {
            margin-bottom: 1.5rem;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #e2e8f0;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .input-group label.required::after {
            content: ' *';
            color: #ef4444;
        }
        
        .input-group input,
        .input-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #475569;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: #334155;
            color: white;
        }
        
        .input-group input::placeholder,
        .input-group textarea::placeholder {
            color: #94a3b8;
        }
        
        .input-group input:focus,
        .input-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            background: #374151;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .input-group small {
            color: #94a3b8;
            font-size: 0.8rem;
            margin-top: 0.3rem;
            display: block;
        }
        
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
        }
        
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Alertas */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #dc2626;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #16a34a;
        }
        
        .alert i {
            font-size: 1.2rem;
            margin-top: 0.1rem;
        }
        
        .alert ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.2rem;
        }
        
        .alert li {
            margin-bottom: 0.3rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .login-body {
                padding: 1rem;
                align-items: flex-start;
                min-height: 100vh;
            }
            
            .single-column-container {
                max-width: 100%;
                gap: 1.5rem;
                margin: 1rem 0;
            }
            
            .centered-login-box {
                padding: 2rem 1.5rem;
            }
            
            .logo-section {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .logo-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .logo-section h1 {
                font-size: 1.5rem;
            }
            
            .logo-section h2 {
                font-size: 1rem;
            }
            
            .requisitos-info-abajo {
                padding: 1.5rem;
            }
            
            .requisitos-header {
                font-size: 1rem;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .requisito-item-pequeno {
                font-size: 0.9rem;
                justify-content: center;
            }
            
            .contacto-admin {
                font-size: 0.8rem;
                flex-direction: column;
                gap: 0.3rem;
            }
        }
        
        @media (max-width: 480px) {
            .login-body {
                padding: 0.5rem;
            }
            
            .single-column-container {
                margin: 0.5rem 0;
                gap: 1rem;
            }
            
            .centered-login-box {
                padding: 1.5rem 1rem;
            }
            
            .requisitos-info-abajo {
                padding: 1rem;
            }
            
            .logo-section h1 {
                font-size: 1.3rem;
            }
            
            .logo-section h2 {
                font-size: 0.9rem;
            }
            
            .requisitos-header {
                font-size: 0.9rem;
            }
            
            .requisito-item-pequeno {
                font-size: 0.85rem;
            }
        }
        
        /* Prevenir zoom en inputs iOS */
        @media (max-width: 768px) {
            input[type="text"],
            input[type="password"],
            input[type="tel"],
            textarea {
                font-size: 16px !important;
            }
        }
    </style>

    <script>
    // Funcionalidad del formulario de registro
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('registroForm');
        const submitBtn = document.getElementById('submitBtn');
        const password = document.getElementById('password');
        const confirmarPassword = document.getElementById('confirmar_password');
        const rfc = document.getElementById('rfc');
        
        // Validación de RFC en tiempo real
        rfc.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
            validateRFC(this);
        });
        
        // Validación de contraseñas
        function validatePasswords() {
            const pwd = password.value;
            const confirmPwd = confirmarPassword.value;
            
            if (pwd && confirmPwd && pwd !== confirmPwd) {
                confirmarPassword.classList.add('invalid');
                showFieldError(confirmarPassword, 'Las contraseñas no coinciden');
            } else {
                confirmarPassword.classList.remove('invalid');
                hideFieldError(confirmarPassword);
            }
        }
        
        password.addEventListener('input', validatePasswords);
        confirmarPassword.addEventListener('input', validatePasswords);
        
        // Validación de RFC
        function validateRFC(field) {
            const value = field.value.trim();
            const rfcPattern = /^[A-Z&Ñ]{4}[0-9]{6}[A-Z0-9]{3}$/;
            
            if (value && !rfcPattern.test(value)) {
                field.classList.add('invalid');
                showFieldError(field, 'Formato de RFC inválido');
            } else {
                field.classList.remove('invalid');
                hideFieldError(field);
            }
        }
        
        // Funciones auxiliares para mostrar errores
        function showFieldError(field, message) {
            hideFieldError(field);
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `<i class="bi bi-exclamation-circle"></i> ${message}`;
            errorDiv.style.color = '#dc2626';
            errorDiv.style.fontSize = '0.8rem';
            errorDiv.style.marginTop = '0.3rem';
            field.parentNode.appendChild(errorDiv);
        }
        
        function hideFieldError(field) {
            const existingError = field.parentNode.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }
        }
        
        // Manejo del envío del formulario
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validaciones finales
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#ef4444';
                    isValid = false;
                } else {
                    field.style.borderColor = '#e5e7eb';
                }
            });
            
            if (!isValid) {
                showMessage('error', 'Por favor completa todos los campos obligatorios.');
                return;
            }
            
            if (password.value !== confirmarPassword.value) {
                showMessage('error', 'Las contraseñas no coinciden.');
                return;
            }
            
            // Mostrar estado de carga
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i> Registrando...';
            
            setTimeout(() => {
                form.submit();
            }, 500);
        });
        
        // Función para mostrar mensajes
        function showMessage(type, message) {
            const existingAlert = document.querySelector('.alert:not([class*="alert-success"]):not([class*="alert-error"])');
            if (existingAlert && !existingAlert.querySelector('ul')) {
                existingAlert.remove();
            }
            
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <i class="bi bi-${type === 'error' ? 'exclamation-triangle-fill' : 'check-circle-fill'}"></i>
                <div><strong>${type === 'error' ? 'Error:' : 'Éxito:'}</strong> ${message}</div>
            `;
            
            form.insertBefore(alert, form.firstChild);
            
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }
            }, 5000);
        }
        
        // Animación de entrada
        const container = document.querySelector('.single-column-container');
        container.style.opacity = '0';
        container.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            container.style.transition = 'all 0.6s ease-out';
            container.style.opacity = '1';
            container.style.transform = 'translateY(0)';
        }, 100);
    });
    
    // Animación de spin
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .input-group input.invalid,
        .input-group textarea.invalid {
            border-color: #ef4444;
            background-color: rgba(239, 68, 68, 0.05);
        }
        
        .error-message {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
    `;
    document.head.appendChild(style);
    </script>
    
</body>
</html>