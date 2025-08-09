<?php
require_once 'config/config.php';

try {
    $pdo = conectarDB();
    echo "<h2>Verificación de Usuarios en el Sistema</h2>";

    // Verificar estructura de la tabla usuarios
    $stmt = $pdo->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Estructura de la tabla usuarios:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Verificar usuarios existentes
    $stmt = $pdo->query("SELECT id, username, nombre, email, rol, activo, fecha_creacion FROM usuarios ORDER BY id");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Usuarios existentes:</h3>";
    if (empty($usuarios)) {
        echo "<p style='color: red;'>⚠️ <strong>NO HAY USUARIOS EN LA BASE DE DATOS</strong></p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Activo</th><th>Fecha Creación</th></tr>";
        foreach ($usuarios as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($user['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['rol']) . "</td>";
            echo "<td>" . ($user['activo'] ? '✅ Sí' : '❌ No') . "</td>";
            echo "<td>" . htmlspecialchars($user['fecha_creacion']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Verificar si existe el usuario admin específicamente
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
    $stmt->execute(['admin']);
    $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h3>Verificación del usuario 'admin':</h3>";
    if ($admin_user) {
        echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<p>✅ <strong>Usuario 'admin' ENCONTRADO</strong></p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . htmlspecialchars($admin_user['id']) . "</li>";
        echo "<li><strong>Username:</strong> " . htmlspecialchars($admin_user['username']) . "</li>";
        echo "<li><strong>Nombre:</strong> " . htmlspecialchars($admin_user['nombre']) . "</li>";
        echo "<li><strong>Email:</strong> " . htmlspecialchars($admin_user['email']) . "</li>";
        echo "<li><strong>Rol:</strong> " . htmlspecialchars($admin_user['rol']) . "</li>";
        echo "<li><strong>Activo:</strong> " . ($admin_user['activo'] ? '✅ Sí' : '❌ No') . "</li>";
        echo "<li><strong>Password Hash:</strong> " . htmlspecialchars(substr($admin_user['password'], 0, 20)) . "...</li>";
        echo "</ul>";
        echo "</div>";

        // Verificar si la contraseña admin123 coincide
        echo "<h4>Verificación de contraseña 'admin123':</h4>";
        if (password_verify('admin123', $admin_user['password'])) {
            echo "<p style='color: green;'>✅ <strong>La contraseña 'admin123' es CORRECTA</strong></p>";
        } else {
            echo "<p style='color: red;'>❌ <strong>La contraseña 'admin123' NO coincide</strong></p>";
            echo "<p>Hash almacenado: " . htmlspecialchars($admin_user['password']) . "</p>";
            echo "<p>Hash de 'admin123': " . password_hash('admin123', PASSWORD_DEFAULT) . "</p>";
        }
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<p>❌ <strong>Usuario 'admin' NO ENCONTRADO</strong></p>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error de conexión: " . htmlspecialchars($e->getMessage()) . "</p>";
}
