<?php
require_once '../../config/config.php';

echo "🔧 Reparando y completando remitos...\n\n";

try {
    $pdo = conectarDB();
    
    // 1. Corregir el estado del remito existente
    echo "1️⃣ Corrigiendo estado del remito existente...\n";
    $pdo->query("UPDATE remitos SET estado = 'pendiente' WHERE estado IS NULL OR estado = ''");
    echo "✅ Estado corregido a 'pendiente'\n\n";
    
    // 2. Verificar proveedores disponibles
    $proveedores = $pdo->query("SELECT * FROM proveedores WHERE activo = 1 LIMIT 5")->fetchAll();
    if (empty($proveedores)) {
        echo "⚠️ No hay proveedores. Creando proveedores...\n";
        
        $proveedores_data = [
            ['DISTR001', 'Distribuidora Central S.A.'],
            ['ALMAC002', 'Almacén Norte LTDA'],
            ['PROV003', 'Proveedor Sur S.R.L.'],
            ['FAST004', 'Fast Supply Corp'],
            ['MEGA005', 'Mega Distribuciones']
        ];
        
        foreach ($proveedores_data as $prov) {
            $stmt = $pdo->prepare("INSERT INTO proveedores (codigo, razon_social, activo, fecha_creacion) VALUES (?, ?, 1, NOW())");
            $stmt->execute($prov);
        }
        
        $proveedores = $pdo->query("SELECT * FROM proveedores WHERE activo = 1 LIMIT 5")->fetchAll();
        echo "✅ Proveedores creados\n\n";
    }
    
    // 3. Verificar productos disponibles
    $productos = $pdo->query("SELECT * FROM productos WHERE activo = 1 LIMIT 15")->fetchAll();
    if (count($productos) < 5) {
        echo "⚠️ Pocos productos. Creando productos...\n";
        
        $productos_data = [
            ['PROD001', 'Tornillo Philips 4x20mm', 2.50, 5.00, 100],
            ['PROD002', 'Tuerca Hexagonal M8', 1.20, 2.80, 200],
            ['PROD003', 'Arandela Plana 8mm', 0.80, 1.50, 500],
            ['PROD004', 'Clavo 2 pulgadas', 0.15, 0.35, 1000],
            ['PROD005', 'Bisagra 3 pulgadas', 12.50, 25.00, 50]
        ];
        
        foreach ($productos_data as $prod) {
            $stmt = $pdo->prepare("INSERT INTO productos (codigo, nombre, precio_compra, precio_venta, stock, stock_minimo, activo, fecha_creacion) VALUES (?, ?, ?, ?, ?, 10, 1, NOW())");
            $stmt->execute($prod);
        }
        
        $productos = $pdo->query("SELECT * FROM productos WHERE activo = 1 LIMIT 15")->fetchAll();
        echo "✅ Productos creados\n\n";
    }
    
    // 4. Agregar productos al remito existente si no tiene
    $remito_productos = $pdo->query("SELECT COUNT(*) FROM remito_detalles WHERE remito_id = 1")->fetchColumn();
    if ($remito_productos == 0) {
        echo "2️⃣ Agregando productos al remito existente...\n";
        
        for ($i = 0; $i < 5; $i++) {
            $producto = $productos[$i];
            $cantidad = rand(5, 50);
            $precio = $producto['precio_compra'];
            $subtotal = $cantidad * $precio;
            
            $stmt = $pdo->prepare("
                INSERT INTO remito_detalles (
                    remito_id, producto_id, cantidad, codigo_producto_proveedor, observaciones
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                1, // remito_id
                $producto['id'],
                $cantidad,
                'PROV-0000002-' . $producto['codigo'],
                $producto['nombre'] . " - $cantidad unidades a $" . number_format($precio, 2)
            ]);
        }
        echo "✅ 5 productos agregados al remito existente\n\n";
    }
    
    // 5. Crear los 2 remitos faltantes
    echo "3️⃣ Creando remitos faltantes...\n";
    
    for ($i = 2; $i <= 3; $i++) {
        $proveedor = $proveedores[($i-1) % count($proveedores)];
        
        // Crear remito
        $stmt = $pdo->prepare("
            INSERT INTO remitos (
                codigo, numero_remito_proveedor, proveedor_id, codigo_proveedor, 
                fecha_entrega, estado, observaciones, usuario_id, fecha_creacion
            ) VALUES (?, ?, ?, ?, ?, 'pendiente', ?, 1, NOW())
        ");
        
        $codigo = 'REM-' . str_pad($i, 6, '0', STR_PAD_LEFT);
        $numero = 'REM-2025-' . str_pad(1000 + $i, 4, '0', STR_PAD_LEFT);
        $fecha_entrega = date('Y-m-d', strtotime("-" . rand(1, 15) . " days"));
        
        $stmt->execute([
            $codigo,
            $numero,
            $proveedor['id'],
            $proveedor['codigo'],
            $fecha_entrega,
            "Remito de prueba #$i - Mercadería variada"
        ]);
        
        $remito_id = $pdo->lastInsertId();
        echo "✅ Remito $codigo creado (ID: $remito_id)\n";
        
        // Agregar productos al remito
        $productos_remito = array_slice($productos, ($i-1)*3, 5); // 5 productos por remito
        
        foreach ($productos_remito as $producto) {
            $cantidad = rand(5, 30);
            $precio = $producto['precio_compra'];
            $subtotal = $cantidad * $precio;
            
            $stmt = $pdo->prepare("
                INSERT INTO remito_detalles (
                    remito_id, producto_id, cantidad, codigo_producto_proveedor, observaciones
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $remito_id,
                $producto['id'],
                $cantidad,
                $proveedor['codigo'] . '-' . $producto['codigo'],
                $producto['nombre'] . " - $cantidad unidades a $" . number_format($precio, 2)
            ]);
        }
        echo "  📦 5 productos agregados al remito $codigo\n";
    }
    
    echo "\n🎉 ¡Reparación completada!\n\n";
    
    // Verificar resultado final
    echo "📊 Estado final:\n";
    $total = $pdo->query("SELECT COUNT(*) FROM remitos")->fetchColumn();
    echo "  Total remitos: $total\n";
    
    $estados = $pdo->query("SELECT estado, COUNT(*) as cantidad FROM remitos GROUP BY estado")->fetchAll();
    foreach ($estados as $estado) {
        echo "  Estado '{$estado['estado']}': {$estado['cantidad']} remitos\n";
    }
    
    $total_productos = $pdo->query("SELECT COUNT(*) FROM remito_detalles")->fetchColumn();
    echo "  Total productos en remitos: $total_productos\n";
    
    echo "\n🔗 Ahora puedes ir a: http://localhost/sistemadgestion5/modulos/compras/remitos.php\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
