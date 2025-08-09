<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once '../../config/config.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['codigo']) || empty($input['codigo'])) {
        throw new Exception('Código no proporcionado');
    }
    
    $codigo = trim($input['codigo']);
    
    // Verificar si el código ya existe
    $sql = "SELECT id, nombre FROM productos WHERE codigo_barras = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$codigo]);
    $producto = $stmt->fetch();
    
    if ($producto) {
        echo json_encode([
            'existe' => true,
            'producto_id' => $producto['id'],
            'producto_nombre' => $producto['nombre']
        ]);
    } else {
        echo json_encode([
            'existe' => false,
            'mensaje' => 'Código disponible'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Error al verificar código: ' . $e->getMessage()
    ]);
}
?>
