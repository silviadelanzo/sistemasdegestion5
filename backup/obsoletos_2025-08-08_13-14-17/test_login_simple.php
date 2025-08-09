<?php
// Script de prueba simple para login
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Prueba de Login Simplificada</h2>";

// Configuración directa de BD
$host = 'localhost';
$dbname = 'sistemasia_inventpro';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<p style='color: green;'>✅ Conectado a la base de datos</p>";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $usuario = $_POST['usuario'] ?? '';
        $pass = $_POST['password'] ?? '';

        echo "<h3>Datos recibidos:</h3>";
        echo "<p>Usuario: " . htmlspecialchars($usuario) . "</p>";
        echo "<p>Contraseña: " . htmlspecialchars($pass) . "</p>";

        // Buscar usuario
        $sql = "SELECT id, username, password, nombre, email, rol, activo FROM usuarios WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        if ($user) {
            echo "<p style='color: green;'>✅ Usuario encontrado en BD</p>";
            echo "<ul>";
            echo "<li>ID: {$user['id']}</li>";
            echo "<li>Username: {$user['username']}</li>";
            echo "<li>Nombre: {$user['nombre']}</li>";
            echo "<li>Rol: {$user['rol']}</li>";
            echo "<li>Activo: " . ($user['activo'] ? 'Sí' : 'No') . "</li>";
            echo "</ul>";

            // Verificar contraseña
            if (password_verify($pass, $user['password'])) {
                echo "<p style='color: green;'>✅ Contraseña correcta</p>";

                // Iniciar sesión
                session_start();
                $_SESSION['id_usuario'] = $user['id'];
                $_SESSION['nombre_usuario'] = $user['nombre'];
                $_SESSION['usuario'] = $user['username'];
                $_SESSION['rol_usuario'] = $user['rol'];

                echo "<p style='color: green;'>✅ Sesión iniciada correctamente</p>";
                echo "<p><a href='menu_principal.php'>Ir al menú principal</a></p>";
            } else {
                echo "<p style='color: red;'>❌ Contraseña incorrecta</p>";

                // Generar nueva contraseña para prueba
                $nueva_password = password_hash($pass, PASSWORD_DEFAULT);
                echo "<p>Hash generado para '{$pass}': " . substr($nueva_password, 0, 30) . "...</p>";
                echo "<p>Hash en BD: " . substr($user['password'], 0, 30) . "...</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Usuario no encontrado</p>";

            // Mostrar usuarios disponibles
            $sql = "SELECT username, activo FROM usuarios";
            $stmt = $pdo->query($sql);
            $usuarios = $stmt->fetchAll();

            echo "<h4>Usuarios disponibles:</h4>";
            foreach ($usuarios as $u) {
                $estado = $u['activo'] ? 'Activo' : 'Inactivo';
                echo "<p>- {$u['username']} ({$estado})</p>";
            }
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error de conexión: " . $e->getMessage() . "</p>";
}
?>

<hr>
<h3>Formulario de Prueba:</h3>
<form method="post" style="max-width: 300px;">
    <div style="margin-bottom: 10px;">
        <label>Usuario:</label><br>
        <input type="text" name="usuario" value="admin" style="width: 100%; padding: 5px;" required>
    </div>
    <div style="margin-bottom: 10px;">
        <label>Contraseña:</label><br>
        <input type="password" name="password" value="admin123" style="width: 100%; padding: 5px;" required>
    </div>
    <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px;">
        Probar Login
    </button>
</form>

<hr>
<p><a href="login.php">Ir al login oficial</a></p>
<p><a href="diagnostico_login.php">Volver al diagnóstico</a></p>