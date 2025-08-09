<?php
require_once 'config/config.php';

echo "<h3>🔧 VERIFICACIÓN FINAL DE BASE DE DATOS</h3>";

try {
    $pdo = conectarDB();
    
    // Verificar tabla productos
    echo "<h4>📊 Verificando tabla productos...</h4>";
    $stmt = $pdo->query("DESCRIBE productos");
    $columnas = $stmt->fetchAll();
    
    $columnas_requeridas = [
        'codigo_interno', 'codigo_barras', 'nombre', 'descripcion',
        'categoria_id', 'lugar_id', 'unidad_medida', 'factor_conversion',
        'precio_compra', 'precio_minorista', 'precio_mayorista',
        'utilidad_minorista', 'utilidad_mayorista', 'moneda_id', 'impuesto_id',
        'stock', 'stock_minimo', 'stock_maximo', 'usar_control_stock',
        'usa_vencimiento', 'fecha_vencimiento', 'alerta_vencimiento_dias',
        'publicar_web'
    ];
    
    $columnas_existentes = array_column($columnas, 'Field');
    $faltantes = array_diff($columnas_requeridas, $columnas_existentes);
    
    if (empty($faltantes)) {
        echo "✅ Tabla productos: COMPLETA<br>";
    } else {
        echo "❌ Faltan columnas: " . implode(', ', $faltantes) . "<br>";
        
        // Generar SQL para agregar columnas faltantes
        $sql_agregar = [];
        foreach ($faltantes as $columna) {
            switch ($columna) {
                case 'codigo_interno':
                    $sql_agregar[] = "ADD COLUMN codigo_interno VARCHAR(50) NOT NULL";
                    break;
                case 'usa_vencimiento':
                    $sql_agregar[] = "ADD COLUMN usa_vencimiento TINYINT(1) DEFAULT 0";
                    break;
                case 'fecha_vencimiento':
                    $sql_agregar[] = "ADD COLUMN fecha_vencimiento DATE NULL";
                    break;
                case 'alerta_vencimiento_dias':
                    $sql_agregar[] = "ADD COLUMN alerta_vencimiento_dias INT DEFAULT 30";
                    break;
                case 'usar_control_stock':
                    $sql_agregar[] = "ADD COLUMN usar_control_stock TINYINT(1) DEFAULT 1";
                    break;
                case 'publicar_web':
                    $sql_agregar[] = "ADD COLUMN publicar_web TINYINT(1) DEFAULT 0";
                    break;
            }
        }
        
        if (!empty($sql_agregar)) {
            echo "<br><strong>🛠️ Ejecutar este SQL para reparar:</strong><br>";
            echo "<code>ALTER TABLE productos " . implode(", ", $sql_agregar) . ";</code><br><br>";
        }
    }
    
    // Verificar tablas auxiliares
    $tablas = ['categorias', 'lugares', 'monedas', 'impuestos'];
    foreach ($tablas as $tabla) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $tabla");
            $count = $stmt->fetchColumn();
            echo "✅ Tabla $tabla: $count registros<br>";
        } catch (Exception $e) {
            echo "❌ Tabla $tabla: ERROR - " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br><h4>🎯 Estado del Sistema:</h4>";
    
    if (empty($faltantes)) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724;'>";
        echo "<strong>🎉 ¡SISTEMA LISTO!</strong><br>";
        echo "El formulario de productos está completamente funcional.<br>";
        echo "<a href='modulos/Inventario/producto_form_definitivo.php' style='color: #007bff;'>";
        echo "👉 <strong>ABRIR FORMULARIO COMPLETO</strong></a>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
        echo "<strong>⚠️ REQUIERE REPARACIÓN</strong><br>";
        echo "Ejecuta el SQL mostrado arriba en phpMyAdmin primero.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage();
}
?>
