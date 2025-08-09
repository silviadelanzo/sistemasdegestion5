<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../../config/config.php';

header('Content-Type: application/json');

try {
    $pdo = conectarDB();
    
    // Obtener el último código secuencial
    $stmt = $pdo->query("SELECT codigo_interno FROM productos WHERE codigo_interno LIKE 'PROD-%' ORDER BY id DESC LIMIT 1");
    $ultimo_codigo = $stmt->fetchColumn();
    
    if ($ultimo_codigo) {
        // Extraer el número del último código (PROD-0000034 -> 34)
        preg_match('/PROD-(\d+)/', $ultimo_codigo, $matches);
        $ultimo_numero = isset($matches[1]) ? intval($matches[1]) : 0;
        $nuevo_numero = $ultimo_numero + 1;
    } else {
        $nuevo_numero = 1;
    }
    
    // Formatear con ceros a la izquierda (PROD-0000035)
    $nuevo_codigo = 'PROD-' . str_pad($nuevo_numero, 7, '0', STR_PAD_LEFT);
    
    echo json_encode([
        'success' => true,
        'nuevo_codigo' => $nuevo_codigo,
        'ultimo_codigo' => $ultimo_codigo,
        'ultimo_numero' => $ultimo_numero ?? 0
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al generar código: ' . $e->getMessage()
    ]);
}
?>
