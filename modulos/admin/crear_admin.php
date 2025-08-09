<?php
require_once 'config/config.php';

echo "<h2>Inicialización del Usuario Admin</h2>";

try {
    $pdo = conectarDB();

    // Verificar si existe el usuario admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE username = ?");
    $stmt->execute(['admin']);
    $existe_admin = $stmt->fetchColumn();

    if ($existe_admin > 0) {
        echo "<p style='color: orange;'>⚠️ El usuario 'admin' ya existe. Actualizando...</p>";

        // Actualizar el usuario admin existente
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET password = ?, 
                nombre = 'Administrador', 
                email = 'admin@sistema.com', 
                rol = 'admin', 
                activo = 1,
                fecha_actualizacion = NOW()
            WHERE username = 'admin'
        ");
        $resultado = $stmt->execute([$password_hash]);

        if ($resultado) {
            echo "<p style='color: green;'>✅ <strong>Usuario 'admin' actualizado correctamente</strong></p>";
        } else {
            echo "<p style='color: red;'>❌ Error al actualizar el usuario admin</p>";
        }
    } else {
        echo "<p style='color: blue;'>🔧 Creando usuario 'admin'...</p>";

        // Crear el usuario admin
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (username, password, nombre, email, rol, activo, fecha_creacion) 
            VALUES ('admin', ?, 'Administrador', 'admin@sistema.com', 'admin', 1, NOW())
        ");
        $resultado = $stmt->execute([$password_hash]);

        if ($resultado) {
            echo "<p style='color: green;'>✅ <strong>Usuario 'admin' creado correctamente</strong></p>";
        } else {
            echo "<p style='color: red;'>❌ Error al crear el usuario admin</p>";
            print_r($stmt->errorInfo());
        }
    }

    // Verificar que todo esté correcto
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
    $stmt->execute(['admin']);
    $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin_user) {
        echo "<h3>Verificación Final:</h3>";
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p><strong>✅ Usuario admin configurado:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Username:</strong> admin</li>";
        echo "<li><strong>Password:</strong> admin123</li>";
        echo "<li><strong>Nombre:</strong> " . htmlspecialchars($admin_user['nombre']) . "</li>";
        echo "<li><strong>Email:</strong> " . htmlspecialchars($admin_user['email']) . "</li>";
        echo "<li><strong>Rol:</strong> " . htmlspecialchars($admin_user['rol']) . "</li>";
        echo "<li><strong>Activo:</strong> " . ($admin_user['activo'] ? 'Sí' : 'No') . "</li>";
        echo "</ul>";
        echo "</div>";

        // Verificar la contraseña
        if (password_verify('admin123', $admin_user['password'])) {
            echo "<p style='color: green; font-weight: bold;'>🔐 Contraseña verificada correctamente</p>";
        } else {
            echo "<p style='color: red;'>❌ Error: La contraseña no se verificó correctamente</p>";
        }

        echo "<h3>🎯 Ahora puedes usar:</h3>";
        echo "<div style='background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p><strong>Usuario:</strong> admin</p>";
        echo "<p><strong>Contraseña:</strong> admin123</p>";
        echo "</div>";

        echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔑 Ir al Login</a></p>";
    } else {
        echo "<p style='color: red;'>❌ Error: No se pudo crear/actualizar el usuario admin</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Detalles técnicos: " . htmlspecialchars($e->getTraceAsString()) . "</p>";
}

echo "<hr>";
echo "<p><a href='verificar_usuarios.php'>🔍 Ver todos los usuarios</a> | <a href='test_conexion.php'>🔗 Test conexión</a></p>";
