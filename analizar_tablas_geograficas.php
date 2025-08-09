<?php
require_once 'config/config.php';

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "🔍 ANÁLISIS DE TABLAS GEOGRÁFICAS\n";
    echo "==================================\n\n";
    
    // Buscar todas las tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 TABLAS ENCONTRADAS:\n";
    foreach($tablas as $tabla) {
        echo "- $tabla\n";
    }
    
    echo "\n🌍 TABLAS GEOGRÁFICAS:\n";
    
    $tablasGeo = ['paises', 'provincias', 'ciudades', 'estados', 'regions'];
    
    foreach($tablasGeo as $tablaGeo) {
        if(in_array($tablaGeo, $tablas)) {
            echo "\n✅ TABLA: $tablaGeo\n";
            
            // Mostrar estructura
            $stmt = $pdo->query("DESCRIBE $tablaGeo");
            $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "Columnas:\n";
            foreach($columnas as $col) {
                echo "  - {$col['Field']} ({$col['Type']})\n";
            }
            
            // Mostrar algunos datos
            $stmt = $pdo->query("SELECT * FROM $tablaGeo LIMIT 10");
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "Datos (primeros 10):\n";
            foreach($datos as $fila) {
                echo "  " . json_encode($fila, JSON_UNESCAPED_UNICODE) . "\n";
            }
        } else {
            echo "❌ NO EXISTE: $tablaGeo\n";
        }
    }
    
    echo "\n📋 ANÁLISIS DE TABLA CLIENTES:\n";
    if(in_array('clientes', $tablas)) {
        $stmt = $pdo->query("DESCRIBE clientes");
        $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Columnas relacionadas con geografía:\n";
        foreach($columnas as $col) {
            $campo = strtolower($col['Field']);
            if(strpos($campo, 'pais') !== false || 
               strpos($campo, 'provincia') !== false || 
               strpos($campo, 'ciudad') !== false ||
               strpos($campo, 'direccion') !== false) {
                echo "  - {$col['Field']} ({$col['Type']})\n";
            }
        }
        
        echo "\nEjemplos de datos geográficos:\n";
        $stmt = $pdo->query("SELECT pais, provincia, ciudad FROM clientes WHERE pais IS NOT NULL LIMIT 5");
        $ejemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($ejemplos as $ejemplo) {
            echo "  " . json_encode($ejemplo, JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    
    echo "\n📋 ANÁLISIS DE TABLA PROVEEDORES:\n";
    if(in_array('proveedores', $tablas)) {
        $stmt = $pdo->query("DESCRIBE proveedores");
        $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Columnas relacionadas con geografía:\n";
        foreach($columnas as $col) {
            $campo = strtolower($col['Field']);
            if(strpos($campo, 'pais') !== false || 
               strpos($campo, 'provincia') !== false || 
               strpos($campo, 'ciudad') !== false ||
               strpos($campo, 'direccion') !== false) {
                echo "  - {$col['Field']} ({$col['Type']})\n";
            }
        }
    }
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
