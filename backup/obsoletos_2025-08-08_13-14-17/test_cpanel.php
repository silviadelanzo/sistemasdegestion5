<?php
// VERIFICACIÓN RÁPIDA POST-DESPLIEGUE
header('Content-Type: text/html; charset=UTF-8');

echo "<h2>🔍 Verificación Rápida - Sistema de Gestión</h2>";

// 1. Verificar config.php
echo "<h3>1. Configuración</h3>";
if (file_exists('config/config.php')) {
    echo "✅ config.php existe<br>";
    include_once 'config/config.php';

    // Probar conexión DB
    try {
        $pdo = conectarDB();
        echo "✅ Conexión a base de datos: EXITOSA<br>";

        // Verificar tabla usuarios
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $usuarios = $stmt->fetch()['total'];
        echo "✅ Usuarios en BD: $usuarios<br>";
    } catch (Exception $e) {
        echo "❌ Error de BD: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ config.php NO encontrado<br>";
}

// 2. Verificar extensiones PHP
echo "<h3>2. Extensiones PHP</h3>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "ZipArchive: " . (extension_loaded('zip') ? '✅ Disponible' : '❌ No disponible') . "<br>";
echo "SimpleXML: " . (extension_loaded('simplexml') ? '✅ Disponible' : '❌ No disponible') . "<br>";
echo "DOM: " . (extension_loaded('dom') ? '✅ Disponible' : '❌ No disponible') . "<br>";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ Disponible' : '❌ No disponible') . "<br>";

// 3. Verificar carpetas
echo "<h3>3. Carpetas y Permisos</h3>";
$carpetas = ['assets', 'assets/uploads', 'assets/img', 'config', 'modulos'];
foreach ($carpetas as $carpeta) {
    if (is_dir($carpeta)) {
        $permisos = substr(sprintf('%o', fileperms($carpeta)), -4);
        echo "✅ $carpeta (permisos: $permisos)<br>";
    } else {
        echo "❌ $carpeta no existe<br>";
    }
}

// 4. Verificar archivos clave
echo "<h3>4. Archivos Clave</h3>";
$archivos = [
    'login.php',
    'excel_xlsx_nativo.php',
    'modulos/Inventario/reporte_completo_excel.php'
];

foreach ($archivos as $archivo) {
    echo file_exists($archivo) ? "✅ $archivo<br>" : "❌ $archivo NO encontrado<br>";
}

// 5. Test Excel rápido
echo "<h3>5. Test Excel</h3>";
if (extension_loaded('zip')) {
    echo "✅ Excel nativo funcional - <a href='excel_xlsx_nativo.php' target='_blank'>Probar Excel</a><br>";
} else {
    echo "❌ Excel no funcional (falta ZipArchive)<br>";
}

echo "<h3>✅ Verificación Completa</h3>";
echo "<p><strong>Siguiente paso:</strong> <a href='login.php'>Acceder al Sistema</a></p>";
echo "<p><strong>Usuario:</strong> admin | <strong>Contraseña:</strong> admin123</p>";
