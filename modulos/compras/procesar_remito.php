<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Método no permitido");
}

try {
    $pdo = conectarDB();
    $pdo->beginTransaction();
    
    // Obtener datos del formulario
    $accion = $_POST['accion'] ?? '';
    $proveedor_id = (int)$_POST['proveedor_id'];
    $numero_remito_proveedor = trim($_POST['numero_remito_proveedor'] ?? '');
    $fecha_entrega = $_POST['fecha_entrega'] ?? date('Y-m-d');
    $estado = $_POST['estado'] ?? 'pendiente';
    $observaciones = trim($_POST['observaciones'] ?? '');
    $productos = $_POST['productos'] ?? [];
    $usuario_id = $_SESSION['usuario_id'] ?? 1;
    
    // Validaciones básicas
    if (empty($proveedor_id)) {
        throw new Exception('Debe seleccionar un proveedor');
    }
    
    if (empty($productos)) {
        throw new Exception('Debe agregar al menos un producto');
    }
    
    // Generar código de remito
    $stmt = $pdo->prepare("SELECT codigo FROM compras WHERE codigo LIKE 'REMI-%' ORDER BY CAST(SUBSTRING(codigo, 6) AS UNSIGNED) DESC LIMIT 1");
    $stmt->execute();
    $ultimo_codigo = $stmt->fetchColumn();
    
    if ($ultimo_codigo) {
        $numero = (int)substr($ultimo_codigo, 5) + 1;
    } else {
        $numero = 1;
    }
    $codigo_remito = 'REMI-' . str_pad($numero, 7, '0', STR_PAD_LEFT);
    
    // Insertar remito principal
    $sql_remito = "INSERT INTO compras (
        codigo, 
        proveedor_id, 
        fecha_compra, 
        fecha_entrega_estimada, 
        estado, 
        observaciones, 
        usuario_id, 
        total,
        numero_remito_proveedor,
        tipo_documento
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, 'remito')";
    
    $stmt = $pdo->prepare($sql_remito);
    $stmt->execute([
        $codigo_remito,
        $proveedor_id,
        date('Y-m-d H:i:s'),
        $fecha_entrega,
        $estado,
        $observaciones,
        $usuario_id,
        $numero_remito_proveedor
    ]);
    
    $remito_id = $pdo->lastInsertId();
    
    // Insertar productos del remito
    foreach ($productos as $producto_data) {
        if (empty($producto_data['producto_id']) || empty($producto_data['cantidad'])) {
            continue; // Saltar productos vacíos
        }
        
        $producto_id = (int)$producto_data['producto_id'];
        $cantidad = (float)$producto_data['cantidad'];
        $codigo_proveedor = $producto_data['codigo_proveedor'] ?? '';
        $unidad = $producto_data['unidad'] ?? 'UN';
        $estado_producto = $producto_data['estado'] ?? 'bueno';
        $observaciones_producto = $producto_data['observaciones'] ?? '';
        
        // Verificar que el producto existe
        $stmt = $pdo->prepare("SELECT nombre FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch();
        
        if (!$producto) {
            throw new Exception("Producto con ID $producto_id no encontrado");
        }
        
        // Insertar detalle del remito
        $sql_detalle = "INSERT INTO compra_detalles (
            compra_id, 
            producto_id, 
            cantidad_pedida,
            cantidad_recibida, 
            precio_unitario, 
            subtotal,
            observaciones
        ) VALUES (?, ?, ?, ?, 0, 0, ?)";
        
        $observaciones_completas = "";
        if (!empty($codigo_proveedor)) {
            $observaciones_completas .= "Código Proveedor: $codigo_proveedor | ";
        }
        if (!empty($unidad)) {
            $observaciones_completas .= "Unidad: $unidad | ";
        }
        if (!empty($estado_producto)) {
            $observaciones_completas .= "Estado: $estado_producto | ";
        }
        if (!empty($observaciones_producto)) {
            $observaciones_completas .= $observaciones_producto;
        }
        
        $stmt = $pdo->prepare($sql_detalle);
        $stmt->execute([
            $remito_id,
            $producto_id,
            $cantidad,
            $cantidad, // cantidad_recibida = cantidad_pedida en remitos
            trim($observaciones_completas, "| ")
        ]);
        
        // Si es confirmado, actualizar stock
        if ($accion === 'confirmar') {
            $stmt = $pdo->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$cantidad, $producto_id]);
        }
    }
    
    $pdo->commit();
    
    // Redirigir según la acción
    if ($accion === 'confirmar') {
        $mensaje = "Remito $codigo_remito confirmado exitosamente";
        $tipo = 'success';
    } else {
        $mensaje = "Remito $codigo_remito guardado como borrador";
        $tipo = 'info';
    }
    
    // Redirigir con mensaje
    header("Location: compras.php?mensaje=" . urlencode($mensaje) . "&tipo=$tipo");
    exit;
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $error = "Error al procesar remito: " . $e->getMessage();
    header("Location: compras_form.php?error=" . urlencode($error));
    exit;
}
?>
