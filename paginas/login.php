<?php
/**
 * Página de Login
 * Sistema GHC - Gestión Hídrica Comunitaria
 */

session_start();
require_once '../config/conexion.php';
require_once '../config/config.php';
require_once '../funciones/sesiones.php';

// Si ya está logueado, redirigir
if (verificar_sesion()) {
    if (es_admin()) {
        header('Location: admin/inicio_admin.php');
    } else {
        header('Location: usuario/inicio.php');
    }
    exit();
}

$error = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'login') {
    $username = limpiar_entrada($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor completa todos los campos';
    } else {
        $query = "SELECT * FROM usuarios WHERE username = ? AND activo = 1";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows == 1) {
            $usuario = $resultado->fetch_assoc();
            if (password_verify($password, $usuario['contraseña'])) {
                // Login exitoso
                $_SESSION['usuario_id'] = $usuario['id_usuario'];
                $_SESSION['username'] = $usuario['username'];
                $_SESSION['nombre'] = $usuario['nombre_completo'];
                $_SESSION['id_rol'] = $usuario['id_rol'];
                
                // Redirigir según rol
                if ($usuario['id_rol'] == ROL_ADMIN) {
                    header('Location: admin/inicio_admin.php');
                } else {
                    header('Location: usuario/inicio.php');
                }
                exit();
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GHC - Iniciar Sesión</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Sistema GHC</h1>
                <h2>Gestión Hídrica Comunitaria</h2>
                <p>Cañada de Flores</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <input type="hidden" name="accion" value="login">
                
                <div class="input-group">
                    <label for="username">Usuario:</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        placeholder="Ingresa tu nombre de usuario"
                    >
                </div>
                
                <div class="input-group">
                    <label for="password">Contraseña:</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        placeholder="Ingresa tu contraseña"
                    >
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    Ingresar al Sistema
                </button>
            </form>
            
            <div class="login-footer">
                <p><a href="registro.php">¿No tienes cuenta? Regístrate aquí</a></p>
                <p><small>Versión 1.0 - Pozo Cañada de Flores</small></p>
            </div>
        </div>
    </div>
    
    <div class="login-bg-info">
        <div class="info-card">
            <h3>Sobre el Sistema</h3>
            <p>Sistema diseñado para la gestión eficiente del pozo comunitario, permitiendo reservas de horarios y monitoreo del consumo de agua.</p>
        </div>
    </div>
</body>
</html>