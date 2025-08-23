<?php
require_once '../config/config.php';

echo "<h1>🔧 Reparar Tabla Productos - Columna codigo_interno</h1>";

try {
    $pdo = conectarDB();
    
    echo "<h3>1️⃣ Verificando estructura actual:</h3>";
    
    $stmt = $pdo->query("DESCRIBE productos");
    $columnas = $stmt->fetchAll();
    
    $tiene_codigo_interno = false;
    foreach ($columnas as $columna) {
        if ($columna['Field'] === 'codigo_interno') {
            $tiene_codigo_interno = true;
            echo "<p style='color: green;'>✅ Columna 'codigo_interno' YA EXISTE</p>";
            break;
        }
    }
    
    if (!$tiene_codigo_interno) {
        echo "<p style='color: red;'>❌ Columna 'codigo_interno' NO EXISTE</p>";
        echo "<h3>2️⃣ Agregando columna codigo_interno:</h3>";
        
        try {
            $pdo->exec("ALTER TABLE productos ADD COLUMN codigo_interno VARCHAR(20) UNIQUE");
            echo "<p style='color: green;'>✅ Columna 'codigo_interno' agregada exitosamente</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Error agregando columna: " . $e->getMessage() . "</p>";
            exit;
        }
    }
    
    echo "<h3>3️⃣ Actualizando productos existentes:</h3>";
    
    // Obtener productos sin código interno
    $stmt = $pdo->query("SELECT id FROM productos WHERE codigo_interno IS NULL OR codigo_interno = ''");
    $productos_sin_codigo = $stmt->fetchAll();
    
    if (count($productos_sin_codigo) > 0) {
        echo "<p style='color: blue;'>📝 Encontrados " . count($productos_sin_codigo) . " productos sin código interno</p>";
        
        foreach ($productos_sin_codigo as $producto) {
            $codigo = 'PROD-' . str_pad($producto['id'], 7, '0', STR_PAD_LEFT);
            try {
                $stmt = $pdo->prepare("UPDATE productos SET codigo_interno = ? WHERE id = ?");
                $stmt->execute([$codigo, $producto['id']]);
                echo "<p style='color: blue;'>✅ Producto ID {$producto['id']} → $codigo</p>";
            } catch (PDOException $e) {
                echo "<p style='color: orange;'>⚠️ Error en producto {$producto['id']}: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p style='color: green;'>✅ Todos los productos ya tienen código interno</p>";
    }
    
    echo "<h3>4️⃣ Verificando último código:</h3>";
    
    try {
        $stmt = $pdo->query("SELECT id, codigo_interno FROM productos WHERE codigo_interno LIKE 'PROD-%' ORDER BY id DESC LIMIT 1");
        $ultimo = $stmt->fetch();
        
        if ($ultimo) {
            echo "<p style='color: purple;'>🎯 Último producto: ID {$ultimo['id']}, Código: {$ultimo['codigo_interno']}</p>";
            $siguiente_id = intval(str_replace(['PROD-', '0'], '', $ultimo['codigo_interno'])) + 1;
            $siguiente_codigo = 'PROD-' . str_pad($siguiente_id, 7, '0', STR_PAD_LEFT);
            echo "<p style='color: magenta;'>➡️ Próximo código: $siguiente_codigo</p>";
        } else {
            echo "<p style='color: gray;'>ℹ️ No hay productos con código PROD- en la base</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>ℹ️ Error verificando códigos: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>🎉 ¡REPARACIÓN COMPLETADA!</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ Columna codigo_interno lista</h4>";
    echo "<p>El sistema ahora puede generar códigos automáticos correctamente.</p>";
    echo "<a href='obtener_ultimo_codigo.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔢 Probar API</a>";
    echo "<a href='modulos/Inventario/producto_form.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Ir al Formulario</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ ERROR:</h3>";
    echo "<p style='color: red; background: #f8d7da; padding: 10px;'>" . $e->getMessage() . "</p>";
}
?>
