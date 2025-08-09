<?php
require_once 'config/config.php';

echo "<h1>🔧 Diagnóstico Completo del Sistema de Login</h1>";

try {
    $pdo = conectarDB();
    echo "<p style='color: green;'>✅ Conexión a base de datos: OK</p>";

    // Verificar si existe la tabla usuarios
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
    $tabla_existe = $stmt->fetch();

    if (!$tabla_existe) {
        echo "<p style='color: red;'>❌ La tabla 'usuarios' NO EXISTE</p>";
        echo "<h3>🔧 Creando tabla usuarios...</h3>";

        // Crear la tabla usuarios
        $sql_crear_tabla = "
        CREATE TABLE `usuarios` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `username` varchar(50) NOT NULL,
          `password` varchar(255) NOT NULL,
          `nombre` varchar(100) NOT NULL,
          `email` varchar(100) DEFAULT NULL,
          `rol` enum('admin','usuario','inventario','vendedor') NOT NULL DEFAULT 'usuario',
          `activo` tinyint(1) NOT NULL DEFAULT 1,
          `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $pdo->exec($sql_crear_tabla);
        echo "<p style='color: green;'>✅ Tabla 'usuarios' creada exitosamente</p>";
    } else {
        echo "<p style='color: green;'>✅ La tabla 'usuarios' existe</p>";
    }

    // Verificar cantidad de usuarios
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $total_usuarios = $stmt->fetch()['total'];

    echo "<p><strong>Total de usuarios en la base:</strong> $total_usuarios</p>";

    if ($total_usuarios == 0) {
        echo "<h3>🔧 No hay usuarios. Creando usuario admin...</h3>";

        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (username, password, nombre, email, rol, activo) 
            VALUES ('admin', ?, 'Administrador', 'admin@sistema.com', 'admin', 1)
        ");

        if ($stmt->execute([$password_hash])) {
            echo "<p style='color: green;'>✅ Usuario admin creado exitosamente</p>";
        } else {
            echo "<p style='color: red;'>❌ Error al crear usuario admin</p>";
            print_r($stmt->errorInfo());
        }
    }

    // Mostrar todos los usuarios
    $stmt = $pdo->query("SELECT id, username, nombre, email, rol, activo FROM usuarios");
    $usuarios = $stmt->fetchAll();

    echo "<h3>👥 Usuarios en el sistema:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Username</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Activo</th></tr>";
    foreach ($usuarios as $user) {
        $activo_badge = $user['activo'] ? '✅' : '❌';
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$user['username']}</strong></td>";
        echo "<td>{$user['nombre']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td><span style='background: #007bff; color: white; padding: 2px 6px; border-radius: 3px;'>{$user['rol']}</span></td>";
        echo "<td>$activo_badge</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Verificar específicamente el usuario admin
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin) {
        echo "<h3>🔑 Verificación del usuario admin:</h3>";
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
        echo "<p><strong>✅ Usuario admin encontrado</strong></p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> {$admin['id']}</li>";
        echo "<li><strong>Username:</strong> {$admin['username']}</li>";
        echo "<li><strong>Nombre:</strong> {$admin['nombre']}</li>";
        echo "<li><strong>Rol:</strong> {$admin['rol']}</li>";
        echo "<li><strong>Activo:</strong> " . ($admin['activo'] ? 'Sí' : 'No') . "</li>";
        echo "</ul>";

        // Probar la verificación de contraseña
        if (password_verify('admin123', $admin['password'])) {
            echo "<p style='color: green; font-weight: bold;'>🔐 Contraseña 'admin123' verificada correctamente</p>";
        } else {
            echo "<p style='color: red;'>❌ La contraseña 'admin123' NO coincide</p>";

            // Actualizar la contraseña
            echo "<p>🔧 Actualizando contraseña...</p>";
            $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt_update = $pdo->prepare("UPDATE usuarios SET password = ? WHERE username = 'admin'");
            if ($stmt_update->execute([$new_hash])) {
                echo "<p style='color: green;'>✅ Contraseña actualizada</p>";
            }
        }
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ No se encontró el usuario admin</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🎯 Credenciales para usar:</h3>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<p><strong>Usuario:</strong> admin</p>";
echo "<p><strong>Contraseña:</strong> admin123</p>";
echo "</div>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔑 Ir al Login</a>";
echo "<a href='test_conexion.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 Test Conexión</a>";
echo "</div>";
