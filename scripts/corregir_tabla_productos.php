<?php
require_once '../config/config.php';

try {
    $pdo = conectarDB();
    
    echo "<h3>🔧 Verificación y Corrección de Tabla Productos</h3>";
    
    // Verificar columnas existentes
    $stmt = $pdo->query("DESCRIBE productos");
    $columnas_existentes = [];
    while ($row = $stmt->fetch()) {
        $columnas_existentes[] = $row['Field'];
    }
    
    echo "<h4>Columnas actuales:</h4>";
    echo "<ul>";
    foreach ($columnas_existentes as $col) {
        echo "<li>$col</li>";
    }
    echo "</ul>";
    
    // Columnas que necesitamos
    $columnas_necesarias = [
        'codigo_interno' => 'VARCHAR(20) UNIQUE',
        'moneda_id' => 'INT NULL',
        'impuesto_id' => 'INT NULL'
    ];
    
    echo "<h4>Verificando columnas necesarias:</h4>";
    foreach ($columnas_necesarias as $columna => $definicion) {
        if (!in_array($columna, $columnas_existentes)) {
            try {
                $pdo->exec("ALTER TABLE productos ADD COLUMN $columna $definicion");
                echo "<p style='color: green;'>✅ Columna '$columna' agregada</p>";
            } catch (PDOException $e) {
                echo "<p style='color: red;'>❌ Error agregando '$columna': " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ Columna '$columna' ya existe</p>";
        }
    }
    
    // Verificar si las columnas problemáticas existen
    if (in_array('codigo_producto', $columnas_existentes)) {
        echo "<h4>🔄 Eliminando columna problemática 'codigo_producto':</h4>";
        try {
            $pdo->exec("ALTER TABLE productos DROP COLUMN codigo_producto");
            echo "<p style='color: green;'>✅ Columna 'codigo_producto' eliminada (causaba conflictos)</p>";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠️ No se pudo eliminar 'codigo_producto': " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h4>✅ Verificación completada</h4>";
    echo "<a href='modulos/Inventario/producto_simple.php' class='btn btn-success'>🚀 Probar Formulario Simple</a>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
