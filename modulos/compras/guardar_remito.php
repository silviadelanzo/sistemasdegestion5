<?php
require_once '../../config/config.php';
session_start();

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Método no permitido");
}

try {
    $pdo = conectarDB();
    $pdo->beginTransaction();
    
    // Datos principales del remito
    $codigo = trim($_POST['codigo']);
    $proveedor_id = (int)$_POST['proveedor_id'];
    $numero_remito_proveedor = trim($_POST['numero_remito_proveedor'] ?? '');
    $fecha_entrega = $_POST['fecha_entrega'];
    $estado = $_POST['estado'] ?? 'borrador';
    $observaciones = trim($_POST['observaciones'] ?? '');
    $accion = $_POST['accion'] ?? 'borrador';
    $usuario_id = $_SESSION['usuario_id'] ?? 1;
    
    // Validaciones básicas
    if (empty($codigo) || empty($proveedor_id) || empty($fecha_entrega)) {
        throw new Exception('Faltan datos obligatorios: código, proveedor o fecha');
    }
    
    if (!isset($_POST['productos']) || empty($_POST['productos'])) {
        throw new Exception('Debe agregar al menos un producto');
    }
    
    // Determinar estado según acción
    $estado_final = ($accion === 'confirmar') ? 'confirmado' : 'borrador';
    
    // Obtener código del proveedor
    $stmt_prov = $pdo->prepare("SELECT codigo FROM proveedores WHERE id = ?");
    $stmt_prov->execute([$proveedor_id]);
    $proveedor_codigo = $stmt_prov->fetchColumn() ?: 'SIN-CODIGO';
    
    // Insertar en tabla remitos
    $sql_remito = "INSERT INTO remitos (
        codigo, 
        numero_remito_proveedor,
        codigo_proveedor,
        proveedor_id, 
        fecha_entrega,
        estado, 
        observaciones, 
        usuario_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_remito = $pdo->prepare($sql_remito);
    $stmt_remito->execute([
        $codigo,
        $numero_remito_proveedor,
        $proveedor_codigo,
        $proveedor_id,
        $fecha_entrega,
        $estado_final,
        $observaciones,
        $usuario_id
    ]);
    
    $remito_id = $pdo->lastInsertId();
    
    // Insertar detalles de productos en remito_detalles
    $sql_detalle = "INSERT INTO remito_detalles (
        remito_id, 
        producto_id, 
        cantidad,
        codigo_producto_proveedor, 
        observaciones
    ) VALUES (?, ?, ?, ?, ?)";
    
    $stmt_detalle = $pdo->prepare($sql_detalle);
    
    foreach ($_POST['productos'] as $producto_data) {
        if (empty($producto_data['producto_id']) || empty($producto_data['cantidad'])) {
            continue; // Saltar productos incompletos
        }
        
        $producto_id = (int)$producto_data['producto_id'];
        $cantidad = (float)$producto_data['cantidad'];
        $codigo_producto_proveedor = trim($producto_data['codigo_proveedor'] ?? '');
        $observaciones_producto = trim($producto_data['observaciones'] ?? '');
        
        // Agregar información de estado y código proveedor a observaciones
        $obs_completas = [];
        if (!empty($producto_data['estado'])) {
            $estados = [
                'bueno' => '✅ Estado: Bueno',
                'regular' => '⚠️ Estado: Regular', 
                'defectuoso' => '❌ Estado: Defectuoso'
            ];
            $obs_completas[] = $estados[$producto_data['estado']] ?? $producto_data['estado'];
        }
        
        if (!empty($producto_data['codigo_proveedor'])) {
            $obs_completas[] = "🏷️ Código Proveedor: " . $producto_data['codigo_proveedor'];
        }
        
        if (!empty($observaciones_producto)) {
            $obs_completas[] = $observaciones_producto;
        }
        
        $observaciones_final = implode(' | ', $obs_completas);
        
        $stmt_detalle->execute([
            $remito_id,
            $producto_id,
            $cantidad,
            $codigo_producto_proveedor,
            $observaciones_final
        ]);
    }
    
    $pdo->commit();
    
    // Redireccionar con mensaje de éxito a la página de remitos
    $mensaje = ($accion === 'confirmar') ? 'Remito confirmado exitosamente' : 'Borrador guardado exitosamente';
    $tipo = ($accion === 'confirmar') ? 'success' : 'info';
    
    header("Location: remitos.php?mensaje=" . urlencode($mensaje) . "&tipo=" . $tipo . "&codigo=" . urlencode($codigo));
    exit;
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    // Redireccionar con mensaje de error
    header("Location: compras_form.php?error=" . urlencode($e->getMessage()));
    exit;
}
?>
