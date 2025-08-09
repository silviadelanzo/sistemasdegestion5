<?php
require_once 'config/config.php';
iniciarSesionSegura();

echo "<h2>🔍 Verificación de Variables de Sesión</h2>";

if (isset($_SESSION['id_usuario'])) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>✅ Sesión Activa</h3>";
    echo "<p><strong>Variables de sesión disponibles:</strong></p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Variable</th><th>Valor</th></tr>";

    foreach ($_SESSION as $key => $value) {
        echo "<tr>";
        echo "<td><code>\$_SESSION['$key']</code></td>";
        echo "<td>" . htmlspecialchars($value) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    // Verificar las variables específicas que usa el navbar
    echo "<h3>🎯 Variables específicas del navbar:</h3>";
    echo "<ul>";
    echo "<li><strong>Nombre usuario:</strong> " . ($_SESSION['nombre_usuario'] ?? 'NO DEFINIDO') . "</li>";
    echo "<li><strong>Usuario (username):</strong> " . ($_SESSION['usuario'] ?? 'NO DEFINIDO') . "</li>";
    echo "<li><strong>Rol usuario:</strong> " . ($_SESSION['rol_usuario'] ?? 'NO DEFINIDO') . "</li>";
    echo "<li><strong>Email usuario:</strong> " . ($_SESSION['correo_electronico_usuario'] ?? 'NO DEFINIDO') . "</li>";
    echo "</ul>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ No hay sesión activa</h3>";
    echo "<p><a href='login.php'>🔑 Ir al Login</a></p>";
    echo "</div>";
}

echo "<hr>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔑 Login</a>";
echo "<a href='menu_principal.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🏠 Menú Principal</a>";
echo "<a href='modulos/compras/compras.php' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🛒 Compras</a>";
echo "</div>";
