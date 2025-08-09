<!DOCTYPE html>
<html>
<head>
    <title>Test Filtrado Productos</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .proveedor { border: 1px solid #ddd; margin: 10px; padding: 10px; }
        .producto { margin-left: 20px; padding: 5px; border-left: 3px solid #007bff; }
    </style>
</head>
<body>
    <h1>🧪 Test de Filtrado de Productos</h1>
    
    <?php
    require_once '../../config/config.php';
    
    // Conectar a la base de datos
    $pdo = conectarDB();
    
    // Crear conexión
    $pdo = conectarDB();
    
    $proveedores = $pdo->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC);
    $productos = $pdo->query("SELECT p.*, c.nombre as categoria_nombre, l.nombre as lugar_nombre 
                             FROM productos p 
                             LEFT JOIN categorias c ON p.categoria_id = c.id 
                             LEFT JOIN lugares l ON p.lugar_id = l.id 
                             WHERE p.activo = 1 ORDER BY p.nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($proveedores as $proveedor) {
        echo "<div class='proveedor'>";
        echo "<h3>🏢 " . htmlspecialchars($proveedor['razon_social']) . " (ID: {$proveedor['id']})</h3>";
        
        $productosDelProveedor = array_filter($productos, function($p) use ($proveedor) {
            return $p['proveedor_principal_id'] == $proveedor['id'];
        });
        
        if (empty($productosDelProveedor)) {
            echo "<p>❌ No hay productos asignados a este proveedor</p>";
        } else {
            foreach ($productosDelProveedor as $producto) {
                echo "<div class='producto'>";
                echo "📦 " . htmlspecialchars($producto['nombre']);
                echo " | 🏷️ " . htmlspecialchars($producto['codigo_proveedor']);
                echo " | ⚖️ " . htmlspecialchars($producto['unidad']);
                echo "</div>";
            }
        }
        
        echo "</div>";
    }
    ?>
    
    <script>
        // Mostrar datos que van al JavaScript
        const productos = <?php echo json_encode($productos); ?>;
        console.log('🔍 Productos cargados:', productos);
        
        // Test de filtrado
        function testFiltrado(proveedorId) {
            console.log(`\n🧪 Test filtrado para proveedor ${proveedorId}:`);
            const filtrados = productos.filter(p => p.proveedor_principal_id == proveedorId);
            console.log('Productos filtrados:', filtrados);
            return filtrados;
        }
        
        // Tests automáticos
        testFiltrado(14); // Distribuidora Central
        testFiltrado(15); // Alimentos del Norte  
        testFiltrado(16); // Tecnología Avanzada
    </script>
    
    <p><a href="compras_form.php">🔄 Volver al Formulario</a></p>
</body>
</html>
