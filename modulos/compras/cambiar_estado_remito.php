<?php
require_once '../../config/config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $pdo = conectarDB();

    $remito_id = (int)$_POST['id'];
    $nuevo_estado = $_POST['estado'];

    // Obtener estado actual
    $stmt = $pdo->prepare("SELECT estado FROM remitos WHERE id = ?");
    $stmt->execute([$remito_id]);
    $remito = $stmt->fetch();
    if (!$remito) {
        echo json_encode(['success' => false, 'message' => 'Remito no encontrado']);
        exit;
    }
    $estado_actual = trim(strtolower($remito['estado']));
    $estados_validos = ['pendiente', 'en_revision', 'cancelado'];

    // Si el estado no es válido o vacío, normalizar y proceder con el cambio
    if (!in_array($estado_actual, $estados_validos) || $estado_actual === '') {
        $estado_actual = 'pendiente';
        $stmt = $pdo->prepare("UPDATE remitos SET estado = 'pendiente' WHERE id = ?");
        $stmt->execute([$remito_id]);
    }

    // Permitir cualquier transición entre los estados válidos
    if (in_array($nuevo_estado, $estados_validos)) {
        if ($estado_actual !== $nuevo_estado) {
            $stmt = $pdo->prepare("UPDATE remitos SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $remito_id]);
            echo json_encode([
                'success' => true,
                'message' => 'Estado actualizado',
                'nuevo_estado' => $nuevo_estado
            ]);
            exit;
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'El remito ya estaba en ese estado',
                'nuevo_estado' => $nuevo_estado
            ]);
            exit;
        }
    }

    // Si no, transición no permitida
    echo json_encode([
        'success' => false,
        'message' => 'Transición de estado no permitida',
        'estado_actual' => $estado_actual
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
