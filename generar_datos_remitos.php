<?php
session_start();
require_once 'config/config.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['usuario_id'] = 1; // Auto-login para testing
    $_SESSION['usuario_nombre'] = 'Admin';
}

echo "<h2>🏭 Generador de Remitos de Prueba con Stock</h2>";

try {
    $pdo = conectarDB();
    
    // Primero verificar que existan las tablas necesarias
    $tables = ['remitos', 'remito_detalles', 'proveedores', 'productos', 'usuarios'];
    $missing_tables = [];
    
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() == 0) {
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<h3>❌ Faltan tablas necesarias:</h3>";
        foreach ($missing_tables as $table) {
            echo "<p>- $table</p>";
        }
        echo "<p><a href='crear_remitos_con_codigo_proveedor.php'>🔧 Crear tablas de remitos</a></p>";
        echo "</div>";
        exit;
    }
    
    // Verificar proveedores
    $proveedores = $pdo->query("SELECT * FROM proveedores WHERE activo = 1 LIMIT 5")->fetchAll();
    if (empty($proveedores)) {
        echo "<h3>⚠️ No hay proveedores activos. Creando proveedores de prueba...</h3>";
        
        // Crear proveedores de prueba
        $proveedores_prueba = [
            ['DISTR001', 'Distribuidora Central S.A.', 'Av. Industrial 123', '011-4444-5555', 'ventas@distrcentral.com'],
            ['ALMAC002', 'Almacén Norte LTDA', 'Ruta 9 Km 45', '011-5555-6666', 'pedidos@almacennorte.com'],
            ['PROV003', 'Proveedor Sur S.R.L.', 'Calle Comercio 789', '011-6666-7777', 'info@proveesur.com'],
            ['FAST004', 'Fast Supply Corp', 'Industrial Park 456', '011-7777-8888', 'orders@fastsupply.com'],
            ['MEGA005', 'Mega Distribuciones', 'Av. Libertador 1001', '011-8888-9999', 'ventas@megadist.com']
        ];
        
        foreach ($proveedores_prueba as $i => $prov) {
            $stmt = $pdo->prepare("INSERT INTO proveedores (codigo, razon_social, direccion, telefono, email, activo, fecha_creacion) VALUES (?, ?, ?, ?, ?, 1, NOW())");
            $stmt->execute($prov);
            echo "✅ Proveedor creado: {$prov[1]}<br>";
        }
        
        $proveedores = $pdo->query("SELECT * FROM proveedores WHERE activo = 1 LIMIT 5")->fetchAll();
    }
    
    // Verificar productos
    $productos = $pdo->query("SELECT * FROM productos WHERE activo = 1 LIMIT 20")->fetchAll();
    if (count($productos) < 10) {
        echo "<h3>⚠️ Pocos productos disponibles. Creando productos de prueba...</h3>";
        
        // Crear productos de prueba
        $productos_prueba = [
            ['PROD001', 'Tornillo Philips 4x20mm', 'Tornillo autorroscante para madera', 2.50, 5.00, 100],
            ['PROD002', 'Tuerca Hexagonal M8', 'Tuerca galvanizada rosca métrica', 1.20, 2.80, 200],
            ['PROD003', 'Arandela Plana 8mm', 'Arandela zinc plated', 0.80, 1.50, 500],
            ['PROD004', 'Clavo 2 pulgadas', 'Clavo de acero galvanizado', 0.15, 0.35, 1000],
            ['PROD005', 'Bisagra 3 pulgadas', 'Bisagra dorada para puerta', 12.50, 25.00, 50],
            ['PROD006', 'Cerradura Exterior', 'Cerradura de seguridad con 3 llaves', 85.00, 150.00, 25],
            ['PROD007', 'Manija Cromada', 'Manija para puerta interior', 22.00, 45.00, 75],
            ['PROD008', 'Pintura Blanca 1L', 'Pintura látex interior lavable', 18.50, 35.00, 80],
            ['PROD009', 'Brocha 2 pulgadas', 'Brocha cerdas naturales', 8.20, 15.00, 120],
            ['PROD010', 'Rodillo + Bandeja', 'Kit completo para pintura', 15.80, 28.00, 60],
            ['PROD011', 'Cable 2.5mm x Metro', 'Cable eléctrico normalizado', 3.20, 6.50, 500],
            ['PROD012', 'Toma Corriente Doble', 'Toma con puesta a tierra', 8.90, 18.00, 100],
            ['PROD013', 'Llave Térmica 16A', 'Protección eléctrica automática', 45.00, 85.00, 30],
            ['PROD014', 'Tubo PVC 110mm x 3m', 'Caño desagüe cloacal', 25.60, 48.00, 40],
            ['PROD015', 'Codo PVC 90° 110mm', 'Accesorio para desagüe', 8.40, 16.50, 150]
        ];
        
        foreach ($productos_prueba as $prod) {
            $stmt = $pdo->prepare("INSERT INTO productos (codigo, nombre, descripcion, precio_compra, precio_venta, stock, stock_minimo, activo, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, 10, 1, NOW())");
            $stmt->execute($prod);
            echo "✅ Producto creado: {$prod[1]}<br>";
        }
        
        $productos = $pdo->query("SELECT * FROM productos WHERE activo = 1 LIMIT 20")->fetchAll();
    }
    
    echo "<h3>🚛 Generando 3 remitos de prueba...</h3>";
    
    // Generar 3 remitos
    for ($i = 1; $i <= 3; $i++) {
        // Seleccionar proveedor aleatorio
        $proveedor = $proveedores[array_rand($proveedores)];
        
        // Crear remito
        $numero_remito = 'REM-' . date('Y') . '-' . str_pad($i + 1000, 4, '0', STR_PAD_LEFT);
        $fecha_entrega = date('Y-m-d', strtotime("-" . rand(1, 30) . " days"));
        
        $stmt = $pdo->prepare("
            INSERT INTO remitos (
                codigo, numero_remito_proveedor, proveedor_id, codigo_proveedor, 
                fecha_entrega, estado, observaciones, usuario_id, fecha_creacion
            ) VALUES (?, ?, ?, ?, ?, 'pendiente', ?, ?, NOW())
        ");
        
        $stmt->execute([
            'REM-' . str_pad($i, 6, '0', STR_PAD_LEFT),
            $numero_remito,
            $proveedor['id'],
            $proveedor['codigo'],
            $fecha_entrega,
            "Remito de prueba #$i - Mercadería variada",
            $_SESSION['usuario_id']
        ]);
        
        $remito_id = $pdo->lastInsertId();
        
        echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4>📋 Remito #$i Creado</h4>";
        echo "<p><strong>ID:</strong> $remito_id</p>";
        echo "<p><strong>Número:</strong> $numero_remito</p>";
        echo "<p><strong>Proveedor:</strong> {$proveedor['razon_social']}</p>";
        echo "<p><strong>Fecha:</strong> $fecha_entrega</p>";
        
        // Agregar 5 productos aleatorios al remito
        $productos_remito = array_rand($productos, 5);
        if (!is_array($productos_remito)) {
            $productos_remito = [$productos_remito];
        }
        
        echo "<h5>📦 Productos incluidos:</h5>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Código</th><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Total</th></tr>";
        
        foreach ($productos_remito as $idx) {
            $producto = $productos[$idx];
            $cantidad = rand(5, 50);
            $precio_unitario = $producto['precio_compra'];
            $subtotal = $cantidad * $precio_unitario;
            
            // Insertar detalle del remito
            $stmt = $pdo->prepare("
                INSERT INTO remito_detalles (
                    remito_id, producto_id, codigo_producto, codigo_producto_proveedor,
                    descripcion, cantidad, precio_unitario, subtotal, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $remito_id,
                $producto['id'],
                $producto['codigo'],
                $proveedor['codigo'] . '-' . $producto['codigo'],
                $producto['nombre'],
                $cantidad,
                $precio_unitario,
                $subtotal
            ]);
            
            // Actualizar stock del producto
            $stmt = $pdo->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$cantidad, $producto['id']]);
            
            echo "<tr>";
            echo "<td>{$producto['codigo']}</td>";
            echo "<td>{$producto['nombre']}</td>";
            echo "<td>$cantidad</td>";
            echo "<td>$" . number_format($precio_unitario, 2) . "</td>";
            echo "<td>$" . number_format($subtotal, 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    
    echo "<h3>🎉 ¡Generación Completada!</h3>";
    echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px;'>";
    echo "<h4>✅ Resumen:</h4>";
    echo "<p>📋 3 remitos creados</p>";
    echo "<p>📦 15 productos agregados a stock</p>";
    echo "<p>🏪 Proveedores verificados</p>";
    echo "</div>";
    
    echo "<h3>🔗 Accesos Rápidos:</h3>";
    echo "<p><a href='modulos/compras/remitos.php' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 18px;'>📋 Ver Remitos Generados</a></p>";
    echo "<p><a href='auto_login_remitos.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔑 Login Automático</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "</div>";
}
?>
