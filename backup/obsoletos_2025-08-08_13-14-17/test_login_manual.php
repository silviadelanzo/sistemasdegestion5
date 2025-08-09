<?php
// Limpiar todas las sesiones
session_start();
session_destroy();
session_start();

require_once 'config/config.php';

echo "<h1>🔄 Test de Login Manual</h1>";

// Simular el proceso de login
$usuario_test = 'admin';
$password_test = 'admin123';

echo "<h3>🔍 Proceso de login paso a paso:</h3>";
echo "<p><strong>Usuario a probar:</strong> $usuario_test</p>";
echo "<p><strong>Contraseña a probar:</strong> $password_test</p>";

try {
    $pdo = conectarDB();
    echo "<p style='color: green;'>✅ Paso 1: Conexión a base de datos OK</p>";

    // Buscar el usuario
    $sql = "SELECT id, username, password, nombre, email, rol, activo 
            FROM usuarios 
            WHERE username = ? AND activo = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_test]);
    $user = $stmt->fetch();

    if ($user) {
        echo "<p style='color: green;'>✅ Paso 2: Usuario encontrado en la base de datos</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> {$user['id']}</li>";
        echo "<li><strong>Username:</strong> {$user['username']}</li>";
        echo "<li><strong>Nombre:</strong> {$user['nombre']}</li>";
        echo "<li><strong>Rol:</strong> {$user['rol']}</li>";
        echo "<li><strong>Activo:</strong> " . ($user['activo'] ? 'Sí' : 'No') . "</li>";
        echo "</ul>";

        // Verificar contraseña
        if (password_verify($password_test, $user['password'])) {
            echo "<p style='color: green;'>✅ Paso 3: Contraseña verificada correctamente</p>";

            // Simular el inicio de sesión
            $_SESSION['id_usuario'] = $user['id'];
            $_SESSION['usuario'] = $user['username'];
            $_SESSION['nombre_usuario'] = $user['nombre'];
            $_SESSION['email_usuario'] = $user['email'];
            $_SESSION['rol_usuario'] = $user['rol'];

            echo "<p style='color: green;'>✅ Paso 4: Sesión iniciada</p>";
            echo "<h3>📋 Variables de sesión creadas:</h3>";
            echo "<ul>";
            foreach ($_SESSION as $key => $value) {
                echo "<li><strong>$key:</strong> $value</li>";
            }
            echo "</ul>";

            echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
            echo "<h3 style='color: green;'>🎉 ¡LOGIN SIMULADO EXITOSO!</h3>";
            echo "<p>Las credenciales funcionan correctamente.</p>";
            echo "<p><strong>Puedes usar:</strong></p>";
            echo "<ul>";
            echo "<li><strong>Usuario:</strong> admin</li>";
            echo "<li><strong>Contraseña:</strong> admin123</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Paso 3: La contraseña NO coincide</p>";
            echo "<p>Hash almacenado: " . substr($user['password'], 0, 30) . "...</p>";

            // Regenerar el hash
            echo "<p>🔧 Regenerando hash de contraseña...</p>";
            $new_hash = password_hash($password_test, PASSWORD_DEFAULT);
            $stmt_update = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            if ($stmt_update->execute([$new_hash, $user['id']])) {
                echo "<p style='color: green;'>✅ Hash actualizado. Intenta el login nuevamente.</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Paso 2: Usuario NO encontrado o inactivo</p>";

        // Crear el usuario admin si no existe
        echo "<p>🔧 Creando usuario admin...</p>";
        $password_hash = password_hash($password_test, PASSWORD_DEFAULT);
        $stmt_create = $pdo->prepare("
            INSERT INTO usuarios (username, password, nombre, email, rol, activo) 
            VALUES (?, ?, 'Administrador', 'admin@sistema.com', 'admin', 1)
            ON DUPLICATE KEY UPDATE 
            password = VALUES(password), 
            activo = 1
        ");

        if ($stmt_create->execute([$usuario_test, $password_hash])) {
            echo "<p style='color: green;'>✅ Usuario admin creado/actualizado</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='login.php' style='background: #28a745; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; margin-right: 10px; font-size: 16px;'>🔑 Probar Login Real</a>";
echo "<a href='menu_principal.php' style='background: #007bff; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🏠 Ir al Menú Principal</a>";
echo "</div>";
