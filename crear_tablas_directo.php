<?php
require_once 'config/config.php';

echo "<h2>🔧 Creando tablas de remitos - MÉTODO DIRECTO</h2>";

try {
    $pdo = conectarDB();
    
    echo "<h3>📋 Creando tabla remitos...</h3>";
    
    // Crear tabla remitos
    $sql_remitos = "CREATE TABLE IF NOT EXISTS remitos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) NOT NULL UNIQUE,
        numero_remito_proveedor VARCHAR(100),
        proveedor_id INT NOT NULL,
        fecha_entrega DATE NOT NULL,
        estado ENUM('borrador', 'confirmado', 'recibido') DEFAULT 'borrador',
        observaciones TEXT,
        usuario_id INT NOT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    )";
    
    $pdo->exec($sql_remitos);
    echo "✅ Tabla 'remitos' creada<br>";
    
    echo "<h3>📋 Creando tabla remito_detalles...</h3>";
    
    // Crear tabla remito_detalles
    $sql_detalles = "CREATE TABLE IF NOT EXISTS remito_detalles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        remito_id INT NOT NULL,
        producto_id INT NOT NULL,
        cantidad DECIMAL(10,2) NOT NULL,
        observaciones TEXT,
        FOREIGN KEY (remito_id) REFERENCES remitos(id) ON DELETE CASCADE,
        FOREIGN KEY (producto_id) REFERENCES productos(id)
    )";
    
    $pdo->exec($sql_detalles);
    echo "✅ Tabla 'remito_detalles' creada<br>";
    
    echo "<h3>📋 Creando índices...</h3>";
    
    // Crear índices
    $indices = [
        "CREATE INDEX IF NOT EXISTS idx_remitos_codigo ON remitos(codigo)",
        "CREATE INDEX IF NOT EXISTS idx_remitos_proveedor ON remitos(proveedor_id)",
        "CREATE INDEX IF NOT EXISTS idx_remitos_fecha ON remitos(fecha_entrega)",
        "CREATE INDEX IF NOT EXISTS idx_remitos_estado ON remitos(estado)",
        "CREATE INDEX IF NOT EXISTS idx_remito_detalles_remito ON remito_detalles(remito_id)"
    ];
    
    foreach ($indices as $indice) {
        try {
            $pdo->exec($indice);
            echo "✅ Índice creado<br>";
        } catch (Exception $e) {
            echo "⚠️ Índice ya existe o error: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h3>🔍 Verificando tablas creadas...</h3>";
    
    // Verificar remitos
    $result_remitos = $pdo->query("SHOW TABLES LIKE 'remitos'");
    if ($result_remitos->rowCount() > 0) {
        echo "✅ <strong>remitos</strong> existe<br>";
        
        $columns = $pdo->query("SHOW COLUMNS FROM remitos")->fetchAll();
        echo "<small>Columnas: ";
        foreach ($columns as $col) {
            echo $col['Field'] . " ";
        }
        echo "</small><br>";
    } else {
        echo "❌ <strong>remitos</strong> NO existe<br>";
    }
    
    // Verificar remito_detalles
    $result_detalles = $pdo->query("SHOW TABLES LIKE 'remito_detalles'");
    if ($result_detalles->rowCount() > 0) {
        echo "✅ <strong>remito_detalles</strong> existe<br>";
        
        $columns = $pdo->query("SHOW COLUMNS FROM remito_detalles")->fetchAll();
        echo "<small>Columnas: ";
        foreach ($columns as $col) {
            echo $col['Field'] . " ";
        }
        echo "</small><br>";
    } else {
        echo "❌ <strong>remito_detalles</strong> NO existe<br>";
    }
    
    // Verificar que ambas tablas existen
    if ($result_remitos->rowCount() > 0 && $result_detalles->rowCount() > 0) {
        echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>🎉 ¡TABLAS CREADAS EXITOSAMENTE!</h3>";
        echo "Las tablas de remitos están listas para usar.<br>";
        echo "Ahora puedes crear remitos y se guardarán correctamente.";
        echo "</div>";
        
        echo "<p><a href='estado_sistema_remitos.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 Verificar Estado del Sistema</a></p>";
        echo "<p><a href='modulos/compras/compras_form.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🆕 Crear Nuevo Remito</a></p>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "❌ No se pudieron crear todas las tablas. Revisa los permisos de la base de datos.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "Detalles: " . $e->getFile() . " línea " . $e->getLine();
    echo "</div>";
}
?>
