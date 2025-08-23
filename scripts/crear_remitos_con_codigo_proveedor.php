<?php
require_once '../config/config.php';

echo "<h2>🔧 Creando tablas de remitos con código de proveedor</h2>";

try {
    $pdo = conectarDB();
    
    echo "<h3>📋 Eliminando tablas existentes (si existen)...</h3>";
    
    // Eliminar tablas si existen para recrearlas
    try {
        $pdo->exec("DROP TABLE IF EXISTS remito_detalles");
        echo "✅ Tabla remito_detalles eliminada<br>";
    } catch (Exception $e) {
        echo "⚠️ remito_detalles no existía<br>";
    }
    
    try {
        $pdo->exec("DROP TABLE IF EXISTS remitos");
        echo "✅ Tabla remitos eliminada<br>";
    } catch (Exception $e) {
        echo "⚠️ remitos no existía<br>";
    }
    
    echo "<h3>📋 Creando tabla remitos con código de proveedor...</h3>";
    
    // Crear tabla remitos CON código de proveedor
    $sql_remitos = "CREATE TABLE remitos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) NOT NULL UNIQUE,
        numero_remito_proveedor VARCHAR(100),
        codigo_proveedor VARCHAR(50),
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
    echo "✅ Tabla 'remitos' creada con código de proveedor<br>";
    
    echo "<h3>📋 Creando tabla remito_detalles...</h3>";
    
    // Crear tabla remito_detalles
    $sql_detalles = "CREATE TABLE remito_detalles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        remito_id INT NOT NULL,
        producto_id INT NOT NULL,
        cantidad DECIMAL(10,2) NOT NULL,
        codigo_producto_proveedor VARCHAR(100),
        observaciones TEXT,
        FOREIGN KEY (remito_id) REFERENCES remitos(id) ON DELETE CASCADE,
        FOREIGN KEY (producto_id) REFERENCES productos(id)
    )";
    
    $pdo->exec($sql_detalles);
    echo "✅ Tabla 'remito_detalles' creada con código de producto del proveedor<br>";
    
    echo "<h3>📋 Creando índices...</h3>";
    
    // Crear índices
    $indices = [
        "CREATE INDEX idx_remitos_codigo ON remitos(codigo)",
        "CREATE INDEX idx_remitos_proveedor ON remitos(proveedor_id)",
        "CREATE INDEX idx_remitos_codigo_proveedor ON remitos(codigo_proveedor)",
        "CREATE INDEX idx_remitos_fecha ON remitos(fecha_entrega)",
        "CREATE INDEX idx_remitos_estado ON remitos(estado)",
        "CREATE INDEX idx_remito_detalles_remito ON remito_detalles(remito_id)"
    ];
    
    foreach ($indices as $indice) {
        try {
            $pdo->exec($indice);
            echo "✅ Índice creado<br>";
        } catch (Exception $e) {
            echo "⚠️ Error en índice: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h3>🔍 Verificando estructura de la tabla remitos...</h3>";
    
    $columns = $pdo->query("SHOW COLUMNS FROM remitos")->fetchAll();
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar que ambas tablas existen
    $remitos_existe = $pdo->query("SHOW TABLES LIKE 'remitos'")->rowCount() > 0;
    $detalles_existe = $pdo->query("SHOW TABLES LIKE 'remito_detalles'")->rowCount() > 0;
    
    if ($remitos_existe && $detalles_existe) {
        echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>🎉 ¡TABLAS CREADAS EXITOSAMENTE!</h3>";
        echo "<p>✅ <strong>remitos</strong> - con código_proveedor</p>";
        echo "<p>✅ <strong>remito_detalles</strong> - con código_producto_proveedor</p>";
        echo "<p>Ahora los remitos pueden filtrarse por código de proveedor.</p>";
        echo "</div>";
        
        echo "<div style='text-align: center; margin: 30px 0;'>";
        echo "<a href='modulos/compras/remitos.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin: 10px; display: inline-block;'>";
        echo "📋 Ver Página de Remitos";
        echo "</a>";
        echo "<a href='modulos/compras/compras_form.php' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin: 10px; display: inline-block;'>";
        echo "🆕 Crear Nuevo Remito";
        echo "</a>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "❌ No se pudieron crear todas las tablas correctamente.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "Detalles: " . $e->getFile() . " línea " . $e->getLine();
    echo "</div>";
}
?>
