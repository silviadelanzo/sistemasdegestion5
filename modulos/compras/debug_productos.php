<?php
require_once '../../config/config.php';

// Crear conexión
$pdo = conectarDB();

echo "<!DOCTYPE html><html><head><title>Debug Productos</title></head><body>";
echo "<h1>🔍 Debug de Productos</h1>";

try {
    // Misma consulta del formulario
    $productos = $pdo->query("SELECT p.*, c.nombre as categoria_nombre, l.nombre as lugar_nombre 
                             FROM productos p 
                             LEFT JOIN categorias c ON p.categoria_id = c.id 
                             LEFT JOIN lugares l ON p.lugar_id = l.id 
                             WHERE p.activo = 1 ORDER BY p.nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>📊 Productos con proveedor_principal_id:</h2>";
    echo "<pre>";
    
    foreach ($productos as $producto) {
        if (!empty($producto['proveedor_principal_id'])) {
            echo "ID: " . $producto['id'] . "\n";
            echo "Nombre: " . $producto['nombre'] . "\n";
            echo "Proveedor ID: " . $producto['proveedor_principal_id'] . "\n";
            echo "Código Proveedor: " . $producto['codigo_proveedor'] . "\n";
            echo "Unidad: " . $producto['unidad'] . "\n";
            echo "---\n";
        }
    }
    
    echo "</pre>";
    
    echo "<h2>🔧 JSON que va al JavaScript:</h2>";
    echo "<textarea style='width:100%;height:200px;'>" . json_encode($productos, JSON_PRETTY_PRINT) . "</textarea>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
