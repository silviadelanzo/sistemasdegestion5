<?php
session_start();
require_once 'config/config.php';

echo "<h2>🔑 Login Automático al Sistema de Remitos</h2>";

try {
    $pdo = conectarDB();
    
    // Hacer login automático con admin
    $stmt = $pdo->prepare("SELECT id, username, password, nombre, rol, activo FROM usuarios WHERE username = ? AND activo = 1");
    $stmt->execute(['admin']);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario && password_verify('admin123', $usuario['password'])) {
        // Login exitoso
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_username'] = $usuario['username'];
        $_SESSION['usuario_rol'] = $usuario['rol'];
        
        // Actualizar último acceso
        $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
        $stmt->execute([$usuario['id']]);
        
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>✅ Login Exitoso</h3>";
        echo "<p><strong>Usuario:</strong> {$usuario['username']}</p>";
        echo "<p><strong>Nombre:</strong> {$usuario['nombre']}</p>";
        echo "<p><strong>Rol:</strong> {$usuario['rol']}</p>";
        echo "<p><strong>Sesión ID:</strong> " . session_id() . "</p>";
        echo "</div>";
        
        echo "<h3>🚀 Acceso Directo:</h3>";
        echo "<p><a href='modulos/compras/remitos.php' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 18px;'>📋 Ir a Sistema de Remitos</a></p>";
        
        // También crear enlaces a otras páginas
        echo "<hr>";
        echo "<h4>🔗 Enlaces Rápidos:</h4>";
        echo "<p><a href='modulos/compras/guardar_remito.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>➕ Nuevo Remito</a></p>";
        echo "<p><a href='menu_principal.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Menú Principal</a></p>";
        
        // Redirigir automáticamente después de 3 segundos
        echo "<script>";
        echo "setTimeout(function() { window.location.href = 'modulos/compras/remitos.php'; }, 3000);";
        echo "</script>";
        echo "<p><em>Redirigiendo automáticamente en 3 segundos...</em></p>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<h3>❌ Error en el Login</h3>";
        echo "<p>No se pudo autenticar el usuario admin</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3>❌ Error de Conexión</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
