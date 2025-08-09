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
    
    // Validar estado
    $estados_validos = ['borrador', 'confirmado', 'recibido'];
    if (!in_array($nuevo_estado, $estados_validos)) {
        throw new Exception('Estado no válido');
    }
    
    // Actualizar estado
    $stmt = $pdo->prepare("UPDATE remitos SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $remito_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo actualizar el estado'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
