<?php
// Configuración específica para servidor
// Copia este archivo y ajusta los valores según tu servidor

// Configuración de la base de datos para servidor web
define('DB_HOST', 'localhost'); // Cambiar por la IP/host de tu servidor MySQL
define('DB_USER', 'tu_usuario'); // Cambiar por tu usuario de MySQL
define('DB_PASS', 'tu_password'); // Cambiar por tu contraseña de MySQL
define('DB_NAME', 'sistemasia_inventpro'); // Cambiar por el nombre de tu base de datos

// Configuración del sistema
define('SISTEMA_NOMBRE', 'Gestion Administrativa');
define('SISTEMA_VERSION', '1.0.0');

// Rutas del sistema para servidor
define('UPLOADS_PATH', __DIR__ . '/../assets/uploads');

// Zona horaria (ajustar según tu ubicación)
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Configuración de errores para servidor (cambiar a false en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Función para conectar a la base de datos
function conectarDB()
{
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $pdo;
    } catch (PDOException $e) {
        // En servidor, registrar error en log en lugar de mostrarlo
        error_log("Error de conexión DB: " . $e->getMessage());
        die("Error de conexión a la base de datos. Contacte al administrador.");
    }
}

// Función para iniciar sesión
function iniciarSesionSegura()
{
    if (session_status() == PHP_SESSION_NONE) {
        // Configuración segura de sesiones para servidor
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1); // Solo HTTPS
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

// Función para formatear moneda
function formatCurrency($amount)
{
    return '$ ' . number_format($amount, 2, ',', '.');
}

// Función para verificar login
function requireLogin($redirect = 'login.php')
{
    if (!isset($_SESSION['id_usuario'])) {
        header("Location: $redirect");
        exit;
    }
}

// Función para verificar permisos
function hasPermission($modulo, $accion)
{
    return isset($_SESSION['id_usuario']);
}

// Función para requerir permisos
function requirePermission($modulo, $accion, $redirect = 'menu_principal.php')
{
    if (!hasPermission($modulo, $accion)) {
        header("Location: $redirect");
        exit;
    }
}

// INSTRUCCIONES PARA CONFIGURAR EN SERVIDOR:
/*
1. ARCHIVOS A SUBIR:
   - Toda la carpeta del proyecto
   - Especialmente la carpeta vendor/ completa
   - Archivos .php, .css, .js
   - Carpeta config/ con este archivo

2. BASE DE DATOS:
   - Crear la base de datos en tu hosting
   - Importar el archivo .sql
   - Ajustar DB_HOST, DB_USER, DB_PASS, DB_NAME arriba

3. PERMISOS:
   - Carpetas: 755 (chmod 755)
   - Archivos PHP: 644 (chmod 644)
   - Carpeta assets/uploads: 777 (para subir archivos)

4. PHP REQUERIDO:
   - Versión: 7.4 o superior
   - Extensiones: zip, xml, mbstring, mysql, curl
   - memory_limit: 256MB o más
   - max_execution_time: 300 o más

5. COMPOSER:
   - Si el servidor lo permite: ejecutar "composer install"
   - Si no: subir la carpeta vendor/ completa

6. HTTPS:
   - Recomendado para producción
   - Cambiar session.cookie_secure a true solo con HTTPS

7. SEGURIDAD:
   - Cambiar error_reporting(0) en producción
   - Usar contraseñas seguras de BD
   - Configurar backup de BD regular
*/
