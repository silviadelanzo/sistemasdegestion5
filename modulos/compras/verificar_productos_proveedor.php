<?php
require_once '../../config/config.php';

// Crear conexión
$pdo = conectarDB();

echo "<!DOCTYPE html><html><head><title>Verificación de Productos por Proveedor</title>";
echo "<style>body{font-family:Arial;margin:20px} table{border-collapse:collapse;width:100%} th,td{border:1px solid #ddd;padding:8px;text-align:left} th{background:#f2f2f2}</style></head><body>";

echo "<h1>🔍 Verificación de Productos por Proveedor</h1>";

try {
    // Obtener productos por proveedor
    $sql = "SELECT pr.razon_social as proveedor, p.nombre as producto, p.codigo_proveedor 
            FROM productos p 
            JOIN proveedores pr ON p.proveedor_principal_id = pr.id 
            WHERE p.proveedor_principal_id IS NOT NULL 
            ORDER BY pr.razon_social, p.codigo_proveedor";
    
    $productos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($productos)) {
        echo "<table>";
        echo "<tr><th>🏢 Proveedor</th><th>📦 Producto</th><th>🏷️ Código Proveedor</th></tr>";
        
        foreach ($productos as $producto) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($producto['proveedor']) . "</td>";
            echo "<td>" . htmlspecialchars($producto['producto']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($producto['codigo_proveedor']) . "</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Resumen por proveedor
        $resumen = [];
        foreach ($productos as $producto) {
            $proveedor = $producto['proveedor'];
            if (!isset($resumen[$proveedor])) {
                $resumen[$proveedor] = 0;
            }
            $resumen[$proveedor]++;
        }
        
        echo "<h2>📊 Resumen por Proveedor:</h2>";
        echo "<ul>";
        foreach ($resumen as $proveedor => $cantidad) {
            echo "<li><strong>$proveedor</strong>: $cantidad productos</li>";
        }
        echo "</ul>";
        
        echo "<p>✅ <strong>Total productos con códigos de proveedor:</strong> " . count($productos) . "</p>";
        
    } else {
        echo "<p>❌ No se encontraron productos asignados a proveedores.</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='compras_form.php'>🔄 Ir al Formulario de Remitos</a></p>";
echo "</body></html>";
?>
