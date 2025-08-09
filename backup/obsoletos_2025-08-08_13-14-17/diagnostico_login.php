<?php
require_once 'config/config.php';

echo "<h2>Diagnóstico del Sistema de Login</h2>";

try {
    $pdo = conectarDB();
    echo "<p style='color: green;'>✅ Conexión a base de datos exitosa</p>";

    // Verificar si existe la tabla usuarios
    $sql = "SHOW TABLES LIKE 'usuarios'";
    $stmt = $pdo->query($sql);
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Tabla 'usuarios' existe</p>";

        // Mostrar estructura de la tabla
        $sql = "DESCRIBE usuarios";
        $stmt = $pdo->query($sql);
        $columns = $stmt->fetchAll();

        echo "<h3>Estructura de la tabla usuarios:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Verificar usuarios existentes
        $sql = "SELECT id, username, nombre, email, rol, activo, ultimo_acceso FROM usuarios";
        $stmt = $pdo->query($sql);
        $usuarios = $stmt->fetchAll();

        echo "<h3>Usuarios en la base de datos:</h3>";
        if (count($usuarios) > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Username</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Activo</th><th>Último Acceso</th></tr>";
            foreach ($usuarios as $user) {
                $activo_texto = $user['activo'] ? 'Sí' : 'No';
                $color = $user['activo'] ? 'green' : 'red';
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td><strong>{$user['username']}</strong></td>";
                echo "<td>{$user['nombre']}</td>";
                echo "<td>{$user['email']}</td>";
                echo "<td>{$user['rol']}</td>";
                echo "<td style='color: {$color};'>{$activo_texto}</td>";
                echo "<td>{$user['ultimo_acceso']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ No hay usuarios en la base de datos</p>";
        }

        // Verificar específicamente el usuario admin
        $sql = "SELECT id, username, password, nombre, email, rol, activo FROM usuarios WHERE username = 'admin'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $admin = $stmt->fetch();

        echo "<h3>Verificación del usuario 'admin':</h3>";
        if ($admin) {
            echo "<p style='color: green;'>✅ Usuario 'admin' encontrado</p>";
            echo "<ul>";
            echo "<li><strong>ID:</strong> {$admin['id']}</li>";
            echo "<li><strong>Username:</strong> {$admin['username']}</li>";
            echo "<li><strong>Nombre:</strong> {$admin['nombre']}</li>";
            echo "<li><strong>Email:</strong> {$admin['email']}</li>";
            echo "<li><strong>Rol:</strong> {$admin['rol']}</li>";
            echo "<li><strong>Activo:</strong> " . ($admin['activo'] ? 'Sí' : 'No') . "</li>";
            echo "<li><strong>Hash de contraseña:</strong> " . substr($admin['password'], 0, 20) . "...</li>";
            echo "</ul>";

            // Verificar la contraseña admin123
            if (password_verify('admin123', $admin['password'])) {
                echo "<p style='color: green;'>✅ La contraseña 'admin123' es correcta</p>";
            } else {
                echo "<p style='color: red;'>❌ La contraseña 'admin123' NO coincide</p>";
                echo "<p>Regenerando contraseña...</p>";

                // Regenerar contraseña
                $nueva_password = password_hash('admin123', PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET password = ? WHERE username = 'admin'";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$nueva_password])) {
                    echo "<p style='color: green;'>✅ Contraseña regenerada correctamente</p>";
                } else {
                    echo "<p style='color: red;'>❌ Error al regenerar contraseña</p>";
                }
            }

            if (!$admin['activo']) {
                echo "<p style='color: orange;'>⚠️ El usuario admin está INACTIVO. Activando...</p>";
                $sql = "UPDATE usuarios SET activo = 1 WHERE username = 'admin'";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>✅ Usuario admin activado</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ Usuario 'admin' NO encontrado</p>";
            echo "<p>Creando usuario admin...</p>";

            // Crear usuario admin
            $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (username, password, nombre, email, rol, activo) 
                    VALUES ('admin', ?, 'Administrador', 'admin@sistema.com', 'admin', 1)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$password_hash])) {
                echo "<p style='color: green;'>✅ Usuario admin creado correctamente</p>";
            } else {
                echo "<p style='color: red;'>❌ Error al crear usuario admin</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ La tabla 'usuarios' no existe</p>";
        echo "<p>Ejecuta el script de base de datos para crear las tablas.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Prueba de Login:</h3>";
echo "<form method='post' action='login.php' style='max-width: 300px;'>";
echo "<div style='margin-bottom: 10px;'>";
echo "<label>Usuario:</label><br>";
echo "<input type='text' name='usuario' value='admin' style='width: 100%; padding: 5px;'>";
echo "</div>";
echo "<div style='margin-bottom: 10px;'>";
echo "<label>Contraseña:</label><br>";
echo "<input type='password' name='password' value='admin123' style='width: 100%; padding: 5px;'>";
echo "</div>";
echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Iniciar Sesión</button>";
echo "</form>";

echo "<p><a href='login.php'>Ir a la página de login</a></p>";
