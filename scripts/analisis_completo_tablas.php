<?php
require_once '../config/config.php';

echo "<h2>🔍 ANÁLISIS COMPLETO DE ESTRUCTURA DE TABLAS</h2>";

try {
    $pdo = conectarDB();
    
    $tablas = ['productos', 'monedas', 'impuestos', 'categorias', 'lugares'];
    
    foreach ($tablas as $tabla) {
        echo "<h3>📋 Tabla: $tabla</h3>";
        try {
            $stmt = $pdo->query("DESCRIBE $tabla");
            $columnas = $stmt->fetchAll();
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr style='background: #f0f0f0;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
            
            foreach ($columnas as $col) {
                echo "<tr>";
                echo "<td><strong>{$col['Field']}</strong></td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Error en tabla $tabla: " . $e->getMessage() . "</p>";
        }
    }
    
    // Verificar datos de muestra
    echo "<h3>📊 Datos de Muestra</h3>";
    
    try {
        echo "<h4>Monedas:</h4>";
        $stmt = $pdo->query("SELECT * FROM monedas LIMIT 3");
        $monedas = $stmt->fetchAll();
        foreach ($monedas as $moneda) {
            echo "<p>ID: {$moneda['id']} - ";
            foreach ($moneda as $key => $value) {
                if ($key !== 'id') echo "$key: $value ";
            }
            echo "</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error monedas: " . $e->getMessage() . "</p>";
    }
    
    try {
        echo "<h4>Impuestos:</h4>";
        $stmt = $pdo->query("SELECT * FROM impuestos LIMIT 3");
        $impuestos = $stmt->fetchAll();
        foreach ($impuestos as $impuesto) {
            echo "<p>ID: {$impuesto['id']} - ";
            foreach ($impuesto as $key => $value) {
                if ($key !== 'id') echo "$key: $value ";
            }
            echo "</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error impuestos: " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error general: " . $e->getMessage() . "</p>";
}
?>
