<?php
// Endpoint: confirmar_remito.php
// Confirma un remito pendiente y actualiza el stock de productos

require_once '../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}


$remitoId = isset($_POST['remito_id']) ? (int)$_POST['remito_id'] : 0;
$cantidades = isset($_POST['cantidades']) && is_array($_POST['cantidades']) ? $_POST['cantidades'] : [];
if (!$remitoId) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de remito faltante']);
    exit;
}

try {
    $pdo = getDb();
    // Verificar que el remito esté pendiente
    $stmt = $pdo->prepare('SELECT estado FROM remitos WHERE id = ?');
    $stmt->execute([$remitoId]);
    $remito = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$remito || $remito['estado'] !== 'pendiente') {
        http_response_code(400);
        echo json_encode(['error' => 'Remito no encontrado o ya confirmado']);
        exit;
    }

    // Obtener los productos y cantidades del remito
    $stmt = $pdo->prepare('SELECT producto_id, cantidad FROM remito_detalles WHERE remito_id = ?');
    $stmt->execute([$remitoId]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$detalles) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay productos en el remito']);
        exit;
    }

    $pdo->beginTransaction();
    // Actualizar cantidades y stock según lo editado
    foreach ($detalles as $d) {
        $pid = $d['producto_id'];
        $nuevaCantidad = isset($cantidades[$pid]) ? (float)$cantidades[$pid] : (float)$d['cantidad'];
        // Actualizar detalle
        $stmtDet = $pdo->prepare('UPDATE remito_detalles SET cantidad = ? WHERE remito_id = ? AND producto_id = ?');
        $stmtDet->execute([$nuevaCantidad, $remitoId, $pid]);
        // Actualizar stock
        $stmtUp = $pdo->prepare('UPDATE productos SET stock = stock + ? WHERE id = ?');
        $stmtUp->execute([$nuevaCantidad, $pid]);
    }
    // Cambiar estado del remito a "confirmado"
    $stmt = $pdo->prepare('UPDATE remitos SET estado = ? WHERE id = ?');
    $stmt->execute(['confirmado', $remitoId]);
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
