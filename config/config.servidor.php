<?php
// Configuración de la base de datos para SERVIDOR CPANEL
// IMPORTANTE: Reemplazar estos valores con los datos reales de tu hosting

define('DB_HOST', 'localhost'); // O la IP que te proporcione tu hosting
define('DB_USER', 'tu_usuario_db'); // Usuario de base de datos de cPanel
define('DB_PASS', 'tu_password_db'); // Contraseña de base de datos de cPanel
define('DB_NAME', 'tu_nombre_db'); // Nombre de base de datos de cPanel

// Configuración del sistema
define('SISTEMA_NOMBRE', 'Gestion Administrativa');
define('SISTEMA_VERSION', '1.0.0');

// Rutas del sistema (ajustadas para servidor)
define('UPLOADS_PATH', __DIR__ . '/../assets/uploads');

// Zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Función para conectar a la base de datos
function conectarDB()
{
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        return $pdo;
    } catch (PDOException $e) {
        error_log("Error de conexión DB: " . $e->getMessage());
        die("Error de conexión a la base de datos. Contacta al administrador.");
    }
}

// Función para iniciar sesión
function iniciarSesionSegura()
{
    if (session_status() == PHP_SESSION_NONE) {
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
function requirePermission($required_roles = [], $redirect = '../index.php')
{
    if (!isset($_SESSION['rol_usuario'])) {
        header("Location: $redirect");
        exit;
    }

    if (!empty($required_roles) && !in_array($_SESSION['rol_usuario'], $required_roles)) {
        header("Location: $redirect");
        exit;
    }
}

// Configuración de errores para producción
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Configuración de memoria y tiempo (ajustado para servidor)
ini_set('memory_limit', '128M');
ini_set('max_execution_time', 30);

// Configuración de uploads (ajustado para servidor)
ini_set('upload_max_filesize', '32M');
ini_set('post_max_size', '32M');
ini_set('max_file_uploads', 20);

// Configuración de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Función para verificar capacidades del servidor
function verificarCapacidadesServidor()
{
    $capacidades = [
        'php_version' => PHP_VERSION,
        'zip_extension' => extension_loaded('zip'),
        'simplexml_extension' => extension_loaded('simplexml'),
        'dom_extension' => extension_loaded('dom'),
        'mysql_support' => extension_loaded('pdo_mysql'),
        'excel_support' => extension_loaded('zip') && extension_loaded('simplexml'),
        'pdf_libraries' => [
            'tcpdf' => class_exists('TCPDF'),
            'dompdf' => class_exists('Dompdf\Dompdf'),
            'mpdf' => class_exists('Mpdf\Mpdf')
        ]
    ];

    return $capacidades;
}
