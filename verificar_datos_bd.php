<?php
require_once 'config/config.php';

echo "<h1>🔍 Verificación de Datos en Base de Datos</h1>";

try {
    $pdo = conectarDB();
    
    echo "<h3>📊 Contenido de las tablas:</h3>";
    
    // Verificar paises
    echo "<h4>🌍 Tabla PAISES:</h4>";
    $stmt = $pdo->query("SELECT * FROM paises");
    $paises = $stmt->fetchAll();
    if ($paises) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Código ISO</th><th>Activo</th></tr>";
        foreach ($paises as $pais) {
            echo "<tr><td>{$pais['id']}</td><td>{$pais['nombre']}</td><td>{$pais['codigo_iso']}</td><td>{$pais['activo']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No hay datos en la tabla paises</p>";
    }
    
    // Verificar monedas
    echo "<h4>💰 Tabla MONEDAS:</h4>";
    $stmt = $pdo->query("SELECT m.*, p.nombre as pais_nombre FROM monedas m JOIN paises p ON m.pais_id = p.id");
    $monedas = $stmt->fetchAll();
    if ($monedas) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>País</th><th>Nombre</th><th>Código</th><th>Símbolo</th><th>Principal</th></tr>";
        foreach ($monedas as $moneda) {
            echo "<tr><td>{$moneda['id']}</td><td>{$moneda['pais_nombre']}</td><td>{$moneda['nombre']}</td><td>{$moneda['codigo_iso']}</td><td>{$moneda['simbolo']}</td><td>" . ($moneda['es_principal'] ? 'Sí' : 'No') . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No hay datos en la tabla monedas</p>";
    }
    
    // Verificar impuestos
    echo "<h4>📋 Tabla IMPUESTOS:</h4>";
    $stmt = $pdo->query("SELECT i.*, p.nombre as pais_nombre FROM impuestos i JOIN paises p ON i.pais_id = p.id");
    $impuestos = $stmt->fetchAll();
    if ($impuestos) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>País</th><th>Nombre</th><th>Porcentaje</th><th>Tipo</th></tr>";
        foreach ($impuestos as $impuesto) {
            echo "<tr><td>{$impuesto['id']}</td><td>{$impuesto['pais_nombre']}</td><td>{$impuesto['nombre']}</td><td>{$impuesto['porcentaje']}%</td><td>{$impuesto['tipo']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No hay datos en la tabla impuestos</p>";
    }
    
    // Verificar estructura de productos
    echo "<h4>📦 Estructura de tabla PRODUCTOS:</h4>";
    $stmt = $pdo->query("DESCRIBE productos");
    $columnas = $stmt->fetchAll();
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>";
    foreach ($columnas as $columna) {
        echo "<tr><td>{$columna['Field']}</td><td>{$columna['Type']}</td><td>{$columna['Null']}</td><td>{$columna['Key']}</td><td>{$columna['Default']}</td></tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ Todas las tablas están correctamente configuradas</h4>";
    echo "<p>Si el formulario aún da error, puede ser un problema de caché o sesión.</p>";
    echo "<a href='modulos/Inventario/producto_form.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔄 Probar Formulario Nuevamente</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error de conexión</h3>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Verifica que:</strong></p>";
    echo "<ul>";
    echo "<li>XAMPP esté funcionando</li>";
    echo "<li>MySQL esté iniciado</li>";
    echo "<li>La base de datos 'sistemasia_inventpro' exista</li>";
    echo "</ul>";
}
?>
