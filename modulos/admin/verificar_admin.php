<?php
require_once 'config/config.php';

echo "<h2>🔧 Verificar y Actualizar Usuario Admin</h2>";

try {
    $pdo = conectarDB();

    // Verificar el usuario admin actual
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin) {
        echo "<h3>👤 Usuario admin actual:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>ID</td><td>{$admin['id']}</td></tr>";
        echo "<tr><td>Username</td><td>{$admin['username']}</td></tr>";
        echo "<tr><td><strong>Nombre</strong></td><td><strong>{$admin['nombre']}</strong></td></tr>";
        echo "<tr><td>Email</td><td>{$admin['email']}</td></tr>";
        echo "<tr><td>Rol</td><td>{$admin['rol']}</td></tr>";
        echo "<tr><td>Activo</td><td>" . ($admin['activo'] ? 'Sí' : 'No') . "</td></tr>";
        echo "</table>";

        // Si el nombre está vacío o es genérico, actualizarlo
        if (empty($admin['nombre']) || $admin['nombre'] === 'Administrador' || $admin['nombre'] === 'admin') {
            echo "<p style='color: orange;'>🔧 Actualizando nombre del administrador...</p>";

            $stmt_update = $pdo->prepare("UPDATE usuarios SET nombre = 'Administrador Sistema' WHERE username = 'admin'");
            if ($stmt_update->execute()) {
                echo "<p style='color: green;'>✅ Nombre actualizado a 'Administrador Sistema'</p>";

                // Mostrar el usuario actualizado
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = 'admin'");
                $stmt->execute();
                $admin_updated = $stmt->fetch();
                echo "<p><strong>Nuevo nombre:</strong> {$admin_updated['nombre']}</p>";
            }
        } else {
            echo "<p style='color: green;'>✅ El nombre del usuario admin está configurado correctamente</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Usuario admin no encontrado</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🎯 Para que aparezca el nombre correcto:</h3>";
echo "<ol>";
echo "<li>El usuario admin debe tener un nombre en la base de datos</li>";
echo "<li>El login debe guardar en \$_SESSION['nombre_usuario']</li>";
echo "<li>El navbar debe mostrar \$_SESSION['nombre_usuario']</li>";
echo "</ol>";

echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔑 Hacer Login Nuevamente</a></p>";
