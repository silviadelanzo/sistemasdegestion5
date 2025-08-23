<?php
require_once '../config/config.php';

echo "<h2>🔍 Verificando estructura de base de datos</h2>";

try {
    $pdo = conectarDB();
    
    // Verificar si existen las tablas de remitos
    echo "<h3>📋 Verificando tablas:</h3>";
    
    $tablas = ['remitos', 'remito_detalles'];
    
    foreach ($tablas as $tabla) {
        $sql = "SHOW TABLES LIKE '$tabla'";
        $result = $pdo->query($sql);
        
        if ($result->rowCount() > 0) {
            echo "✅ Tabla <strong>$tabla</strong> existe<br>";
            
            // Mostrar estructura
            $structure = $pdo->query("DESCRIBE $tabla")->fetchAll();
            echo "<ul>";
            foreach ($structure as $column) {
                echo "<li>{$column['Field']} - {$column['Type']}</li>";
            }
            echo "</ul>";
        } else {
            echo "❌ Tabla <strong>$tabla</strong> NO existe<br>";
        }
    }
    
    // Verificar remitos en tabla compras
    echo "<h3>🚨 Remitos en tabla compras:</h3>";
    $remitos_compras = $pdo->query("SELECT COUNT(*) as count FROM compras WHERE codigo LIKE 'REMI-%'")->fetch();
    echo "Remitos encontrados en compras: <strong>{$remitos_compras['count']}</strong><br>";
    
    if ($remitos_compras['count'] > 0) {
        $remitos = $pdo->query("SELECT codigo, estado, fecha_creacion FROM compras WHERE codigo LIKE 'REMI-%'")->fetchAll();
        echo "<ul>";
        foreach ($remitos as $remito) {
            echo "<li>{$remito['codigo']} - {$remito['estado']} - {$remito['fecha_creacion']}</li>";
        }
        echo "</ul>";
    }
    
    // Verificar datos en tabla remitos
    echo "<h3>📊 Datos en tabla remitos:</h3>";
    if ($pdo->query("SHOW TABLES LIKE 'remitos'")->rowCount() > 0) {
        $remitos_tabla = $pdo->query("SELECT COUNT(*) as count FROM remitos")->fetch();
        echo "Remitos en tabla remitos: <strong>{$remitos_tabla['count']}</strong><br>";
        
        if ($remitos_tabla['count'] > 0) {
            $remitos = $pdo->query("SELECT codigo, estado, fecha_creacion FROM remitos")->fetchAll();
            echo "<ul>";
            foreach ($remitos as $remito) {
                echo "<li>{$remito['codigo']} - {$remito['estado']} - {$remito['fecha_creacion']}</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
