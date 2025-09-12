<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

// 1. Verificación básica del método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: compras.php');
    exit;
}

$pdo = conectarDB();

// 2. Recolección y limpieza de datos del formulario
$orden_id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$numero_orden = $_POST['numero_orden'] ?? null;
$proveedor_id = $_POST['proveedor_id'] ?? null;
$fecha_compra = $_POST['fecha_compra'] ?? date('Y-m-d');
$condicion_pago = $_POST['condicion_pago'] ?? 'Contado';
$deposito_id = !empty($_POST['deposito_id']) ? (int)$_POST['deposito_id'] : null;
$observaciones = $_POST['observaciones'] ?? '';
$usuario_id = $_SESSION['id_usuario'] ?? null; // Asumiendo que el id de usuario está en sesión
$estado_id = !empty($_POST['estado_id']) ? (int)$_POST['estado_id'] : 1; // Default to 1 if not provided

$productos = $_POST['productos'] ?? [];

// 4. Cálculo de total (sin IVA)
$total_calculado = 0;
if(isset($productos['id'])){
    foreach ($productos['id'] as $key => $producto_id) {
        $cantidad = (float)($productos['cantidad'][$key] ?? 0);
        $precio = (float)($productos['precio'][$key] ?? 0);
        $total_calculado += $cantidad * $precio;
    }
}




// 3. Validación de datos críticos
if (empty($proveedor_id) || empty($productos['id']) || empty($numero_orden) || empty($usuario_id)) {
    $_SESSION['error_message'] = 'Faltan datos críticos: Proveedor, productos, número de orden o usuario.';
    header('Location: compra_form.php' . ($orden_id ? '?id=' . $orden_id : ''));
    exit;
}

try {
    // 5. Iniciar transacción
    $pdo->beginTransaction();

    if ($orden_id) {
        // --- LÓGICA DE ACTUALIZACIÓN ---
        $sql_orden = "UPDATE oc_ordenes SET 
                        proveedor_id = :proveedor_id, 
                        fecha_orden = :fecha_orden, 
                        condicion_pago = :condicion_pago, 
                        deposito_id = :deposito_id,
                        estado_id = :estado_id,
                        observaciones = :observaciones, 
                        total = :total
                      WHERE id_orden = :id_orden";
        
        $stmt_orden = $pdo->prepare($sql_orden);
        $stmt_orden->execute([
            ':proveedor_id' => $proveedor_id,
            ':fecha_orden' => $fecha_compra,
            ':condicion_pago' => $condicion_pago,
            ':deposito_id' => $deposito_id,
            ':estado_id' => $estado_id,
            ':observaciones' => $observaciones,
            ':total' => $total_calculado,
            ':id_orden' => $orden_id
        ]);

        // Limpiar detalles antiguos para reemplazarlos
        $stmt_delete = $pdo->prepare("DELETE FROM oc_detalle WHERE id_orden = ?");
        $stmt_delete->execute([$orden_id]);

    } else {
        // --- LÓGICA DE CREACIÓN ---
        $sql_orden = "INSERT INTO oc_ordenes 
                        (numero_orden, proveedor_id, fecha_orden, condicion_pago, deposito_id, observaciones, total, usuario_id, estado_id) 
                      VALUES 
                        (:numero_orden, :proveedor_id, :fecha_orden, :condicion_pago, :deposito_id, :observaciones, :total, :usuario_id, :estado_id)";
        
        $stmt_orden = $pdo->prepare($sql_orden);
        $stmt_orden->execute([
            ':numero_orden' => $numero_orden,
            ':proveedor_id' => $proveedor_id,
            ':fecha_orden' => $fecha_compra,
            ':condicion_pago' => $condicion_pago,
            ':deposito_id' => $deposito_id,
            ':observaciones' => $observaciones,
            ':total' => $total_calculado,
            ':usuario_id' => $usuario_id,
            ':estado_id' => $estado_id
        ]);
        
        // Obtener el ID de la nueva orden insertada
        $orden_id = $pdo->lastInsertId();
    }

    // 6. Insertar los detalles de la orden (común para crear y actualizar)
    $sql_detalle = "INSERT INTO oc_detalle 
                        (id_orden, producto_id, codigo_barra, cantidad, precio_unitario) 
                      VALUES 
                        (:id_orden, :producto_id, :codigo_barra, :cantidad, :precio)";
    $stmt_detalle = $pdo->prepare($sql_detalle);

    foreach ($productos['id'] as $key => $producto_id) {
        $cantidad = (float)($productos['cantidad'][$key] ?? 0);
        $precio = (float)($productos['precio'][$key] ?? 0);
        $codigo_barra = $productos['codigo_barra'][$key] ?? null;
        
        if ($cantidad > 0 && $precio >= 0) {
            $stmt_detalle->execute([
                ':id_orden' => $orden_id,
                ':producto_id' => $producto_id,
                ':codigo_barra' => $codigo_barra,
                ':cantidad' => $cantidad,
                ':precio' => $precio
            ]);
        }
    }

    // 7. Confirmar transacción
    $pdo->commit();

    $_SESSION['success_message'] = "Orden de compra guardada exitosamente con el número " . htmlspecialchars($numero_orden);
    // Redirigir a una página de detalle (asumiendo que existe o se creará)
    header('Location: compra_detalle.php?id=' . $orden_id); 
    exit;

} catch (Exception $e) {
    // 8. Revertir en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al guardar la orden de compra: " . $e->getMessage());
    $_SESSION['error_message'] = "Error al guardar la orden de compra. Por favor, intente de nuevo. Detalle: " . $e->getMessage();
    header('Location: compra_form.php' . ($orden_id ? '?id=' . $orden_id : ''));
    exit;
}
?>