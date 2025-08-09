<?php
echo "<h2>🔍 DIAGNÓSTICO DE LOGIN Y DEBUG</h2>";
echo "<hr>";

// 1. Verificar configuración de sesiones
echo "<h3>1️⃣ CONFIGURACIÓN DE SESIONES</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Configuración</th><th>Valor</th></tr>";
echo "<tr><td>Session Status</td><td>" . session_status() . " (" . 
    (session_status() == PHP_SESSION_NONE ? "No iniciada" : 
     (session_status() == PHP_SESSION_ACTIVE ? "Activa" : "Deshabilitada")) . ")</td></tr>";
echo "<tr><td>Session ID</td><td>" . (session_id() ?: "Sin ID") . "</td></tr>";
echo "<tr><td>Session Name</td><td>" . session_name() . "</td></tr>";
echo "<tr><td>Session Save Path</td><td>" . session_save_path() . "</td></tr>";
echo "<tr><td>Session Cookie Params</td><td>" . json_encode(session_get_cookie_params()) . "</td></tr>";
echo "</table>";

echo "<h4>Variables de Sesión Actuales:</h4>";
if (session_status() == PHP_SESSION_ACTIVE) {
    if (!empty($_SESSION)) {
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ Sesión activa pero sin variables</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Sesión no iniciada</p>";
}

echo "<hr>";

// 2. Verificar información del servidor
echo "<h3>2️⃣ INFORMACIÓN DEL SERVIDOR</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Variable</th><th>Valor</th></tr>";
echo "<tr><td>HTTP_HOST</td><td>" . ($_SERVER['HTTP_HOST'] ?? 'No definido') . "</td></tr>";
echo "<tr><td>SERVER_NAME</td><td>" . ($_SERVER['SERVER_NAME'] ?? 'No definido') . "</td></tr>";
echo "<tr><td>REQUEST_URI</td><td>" . ($_SERVER['REQUEST_URI'] ?? 'No definido') . "</td></tr>";
echo "<tr><td>HTTP_USER_AGENT</td><td>" . ($_SERVER['HTTP_USER_AGENT'] ?? 'No definido') . "</td></tr>";
echo "<tr><td>REMOTE_ADDR</td><td>" . ($_SERVER['REMOTE_ADDR'] ?? 'No definido') . "</td></tr>";
echo "<tr><td>HTTP_REFERER</td><td>" . ($_SERVER['HTTP_REFERER'] ?? 'No definido') . "</td></tr>";
echo "</table>";

echo "<hr>";

// 3. Verificar procesos PHP/Debug activos
echo "<h3>3️⃣ PROCESOS PHP Y DEBUG</h3>";

// Verificar si Xdebug está activo
echo "<h4>Xdebug Status:</h4>";
if (extension_loaded('xdebug')) {
    echo "<p style='color: red;'>🔴 <strong>Xdebug está ACTIVO</strong></p>";
    echo "<ul>";
    if (function_exists('xdebug_info')) {
        echo "<li>Función xdebug_info disponible</li>";
    }
    if (function_exists('xdebug_is_debugger_active')) {
        echo "<li>Debugger activo: " . (xdebug_is_debugger_active() ? "SÍ" : "NO") . "</li>";
    }
    if (ini_get('xdebug.mode')) {
        echo "<li>Modo Xdebug: " . ini_get('xdebug.mode') . "</li>";
    }
    if (ini_get('xdebug.start_with_request')) {
        echo "<li>Start with request: " . ini_get('xdebug.start_with_request') . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: green;'>✅ Xdebug NO está cargado</p>";
}

echo "<h4>Headers HTTP:</h4>";
$headers = apache_request_headers();
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Header</th><th>Valor</th></tr>";
foreach ($headers as $header => $value) {
    $color = '';
    if (stripos($header, 'cookie') !== false) $color = 'background-color: #fff3cd;';
    if (stripos($header, 'debug') !== false) $color = 'background-color: #f8d7da;';
    echo "<tr style='$color'><td>$header</td><td>$value</td></tr>";
}
echo "</table>";

echo "<hr>";

// 4. Probar conexión a base de datos
echo "<h3>4️⃣ PRUEBA DE CONEXIÓN DB</h3>";
try {
    require_once 'config/config.php';
    $pdo = conectarDB();
    echo "<p style='color: green;'>✅ Conexión a DB exitosa</p>";
    
    // Verificar usuarios
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, COUNT(CASE WHEN activo = 1 THEN 1 END) as activos FROM usuarios");
    $stmt->execute();
    $usuarios = $stmt->fetch();
    echo "<p>👥 Usuarios total: {$usuarios['total']} | Activos: {$usuarios['activos']}</p>";
    
    // Mostrar usuarios activos
    $stmt = $pdo->prepare("SELECT username, nombre, rol, ultimo_acceso FROM usuarios WHERE activo = 1 LIMIT 5");
    $stmt->execute();
    $usuarios_lista = $stmt->fetchAll();
    
    if (!empty($usuarios_lista)) {
        echo "<h5>Usuarios disponibles:</h5>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Username</th><th>Nombre</th><th>Rol</th><th>Último Acceso</th></tr>";
        foreach ($usuarios_lista as $user) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($user['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($user['rol']) . "</td>";
            echo "<td>" . ($user['ultimo_acceso'] ? $user['ultimo_acceso'] : 'Nunca') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error DB: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 5. Sugerencias de solución
echo "<h3>5️⃣ DIAGNÓSTICO Y SOLUCIONES</h3>";

echo "<div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff;'>";
echo "<h4>🔍 Posibles causas del problema de login:</h4>";
echo "<ol>";
echo "<li><strong>Sesiones:</strong> Problemas con configuración de sesiones PHP</li>";
echo "<li><strong>Cookies:</strong> Diferencias en manejo de cookies entre VS Code y navegador</li>";
echo "<li><strong>Headers:</strong> VS Code puede enviar headers diferentes</li>";
echo "<li><strong>Debug activo:</strong> Xdebug puede interferir con el flujo normal</li>";
echo "<li><strong>Cache:</strong> Diferencias en cache entre accesos</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 10px;'>";
echo "<h4>🔧 Soluciones recomendadas:</h4>";
echo "<ol>";
echo "<li><strong>Deshabilitar Xdebug temporalmente</strong></li>";
echo "<li><strong>Limpiar sesiones y cookies</strong></li>";
echo "<li><strong>Verificar configuración de VS Code</strong></li>";
echo "<li><strong>Usar navegador externo para login crítico</strong></li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<h4>🛠️ Acciones Rápidas:</h4>";
echo "<a href='?action=clear_sessions' class='btn btn-warning' style='background-color: #ffc107; color: black; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>🗑️ Limpiar Sesiones</a>";
echo "<a href='?action=test_login' class='btn btn-info' style='background-color: #17a2b8; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>🔐 Test Login</a>";
echo "<a href='login.php' class='btn btn-primary' style='background-color: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;'>↩️ Ir a Login</a>";

// Procesar acciones
if (isset($_GET['action'])) {
    echo "<hr>";
    switch ($_GET['action']) {
        case 'clear_sessions':
            session_start();
            session_destroy();
            session_start();
            echo "<div style='color: green;'>✅ Sesiones limpiadas. <a href='?'>Recargar página</a></div>";
            break;
            
        case 'test_login':
            echo "<div style='background-color: #e7f3ff; padding: 15px; border: 1px solid #bee5eb;'>";
            echo "<h5>🔐 Formulario de Test Login</h5>";
            echo "<form method='POST' action='login.php'>";
            echo "<input type='text' name='usuario' placeholder='Usuario' style='margin: 5px; padding: 8px;'><br>";
            echo "<input type='password' name='password' placeholder='Contraseña' style='margin: 5px; padding: 8px;'><br>";
            echo "<button type='submit' style='margin: 5px; padding: 8px 15px; background-color: #28a745; color: white; border: none;'>🚀 Login Test</button>";
            echo "</form>";
            echo "</div>";
            break;
    }
}
?>
