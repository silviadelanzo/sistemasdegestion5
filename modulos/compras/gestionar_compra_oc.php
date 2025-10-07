<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();

try {
    $pdo->beginTransaction();
    
    $id = $_POST['id'] ?? 0;
    $proveedor_id = $_POST['proveedor_id'] ?? $_POST['proveedor_id_hidden'] ?? '';
    $numero_orden = $_POST['numero_orden'] ?? '';
    $fecha_compra = $_POST['fecha_compra'] ?? '';
    $condicion_pago = $_POST['condicion_pago'] ?? '';
            $estado_id = $_POST['estado_id'] ?? 2;
    $deposito_id = $_POST['deposito_id'] ?? null;
    $observaciones = $_POST['observaciones'] ?? '';
    $productos = $_POST['productos'] ?? [];

    // Validaciones
    if (empty($proveedor_id)) {
        throw new Exception('El proveedor es obligatorio');
    }
    if (empty($fecha_compra)) {
        throw new Exception('La fecha de compra es obligatoria');
    }
    if (empty($productos['id']) || count($productos['id']) == 0) {
        throw new Exception('Debe agregar al menos un producto');
    }

    $total = 0;
    
    if ($id > 0) {
        // EDITAR orden existente
        
        // Actualizar la orden principal
        $stmt = $pdo->prepare("
            UPDATE oc_ordenes SET 
                proveedor_id = ?, 
                fecha_orden = ?, 
                condicion_pago = ?, 
                estado_id = ?, 
                deposito_id = ?, 
                observaciones = ?
            WHERE id_orden = ?
        ");
        
        $stmt->execute([
            $proveedor_id,
            $fecha_compra,
            $condicion_pago,
            $estado_id,
            $deposito_id,
            $observaciones,
            $id
        ]);
        
        // Eliminar detalles existentes
        $stmt = $pdo->prepare("DELETE FROM oc_detalle WHERE id_orden = ?");
        $stmt->execute([$id]);
        
    } else {
        // CREAR nueva orden
        
        $stmt = $pdo->prepare("
            INSERT INTO oc_ordenes (
                numero_orden, fecha_orden, proveedor_id, condicion_pago, 
                usuario_id, estado_id, deposito_id, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $numero_orden,
            $fecha_compra,
            $proveedor_id,
            $condicion_pago,
            $_SESSION['user_id'],
            $estado_id,
            $deposito_id,
            $observaciones
        ]);
        
        $id = $pdo->lastInsertId();
        
        // Registrar en auditoría la creación de la orden
        $stmt_auditoria = $pdo->prepare("
            INSERT INTO auditoria (
                tabla_afectada, accion, registro_id, detalle, 
                usuario_id, fecha
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $detalle = json_encode([
            'id_orden' => $id,
            'numero_orden' => $numero_orden,
            'proveedor_id' => $proveedor_id,
            'fecha_orden' => $fecha_compra,
            'condicion_pago' => $condicion_pago,
            'estado_id' => $estado_id,
            'deposito_id' => $deposito_id,
            'accion' => 'Nueva orden creada'
        ]);
        
        $stmt_auditoria->execute([
            'oc_ordenes',
            'INSERT',
            $id,
            $detalle,
            $_SESSION['user_id'] ?? 1
        ]);
    }
    
    // Debug: Log de productos recibidos
    error_log("DEBUG - Productos recibidos: " . print_r($productos, true));
    
    // Insertar productos
    if (isset($productos['id'])) {
        $productos_insertados = 0;
        for ($i = 0; $i < count($productos['id']); $i++) {
            $producto_id = $productos['id'][$i];
            $cantidad = floatval($productos['cantidad'][$i]);
            $precio = floatval($productos['precio'][$i]);
            $codigo_barra = $productos['codigo_barra'][$i] ?? '';
            
            error_log("DEBUG - Procesando producto $i: ID=$producto_id, Cantidad=$cantidad, Precio=$precio");
            
            if ($producto_id && $cantidad > 0 && $precio >= 0) {
                $subtotal = $cantidad * $precio;
                $total += $subtotal;
                
                $stmt = $pdo->prepare("
                    INSERT INTO oc_detalle (
                        id_orden, producto_id, codigo_barra, cantidad, precio_unitario
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $id,
                    $producto_id,
                    $codigo_barra,
                    $cantidad,
                    $precio
                ]);
                
                $productos_insertados++;
                error_log("DEBUG - Producto $producto_id insertado correctamente");
            } else {
                error_log("DEBUG - Producto $i saltado: datos incompletos");
            }
        }
        error_log("DEBUG - Total productos insertados: $productos_insertados");
    }
    
    // Actualizar el total en la orden
    $stmt = $pdo->prepare("UPDATE oc_ordenes SET total = ? WHERE id_orden = ?");
    $stmt->execute([$total, $id]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = 'Orden de compra guardada exitosamente';
    header('Location: compras.php');
    exit;
    
} catch (Exception $e) {
    $pdo->rollback();
    $_SESSION['error_message'] = 'Error al guardar la orden: ' . $e->getMessage();
    header('Location: compra_form.php' . ($id > 0 ? '?id=' . $id : ''));
    exit;
}
?>