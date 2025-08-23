<?php
require_once '../config/config.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $pdo = conectarDB();
    
    // Obtener el último ID de la tabla productos
    $stmt = $pdo->query("SELECT MAX(id) as ultimo_id FROM productos");
    $resultado = $stmt->fetch();
    
    $ultimo_id = $resultado['ultimo_id'] ?? 0;
    $siguiente_id = $ultimo_id + 1;
    
    // Generar el código con formato PROD-0000XXX
    $codigo_generado = 'PROD-' . str_pad($siguiente_id, 7, '0', STR_PAD_LEFT);
    
    // Verificar que no exista ese código (por si acaso)
    $stmt = $pdo->prepare("SELECT COUNT(*) as existe FROM productos WHERE codigo_interno = ?");
    $stmt->execute([$codigo_generado]);
    $existe = $stmt->fetch()['existe'];
    
    if ($existe > 0) {
        // Si existe, buscar el siguiente disponible
        do {
            $siguiente_id++;
            $codigo_generado = 'PROD-' . str_pad($siguiente_id, 7, '0', STR_PAD_LEFT);
            $stmt->execute([$codigo_generado]);
            $existe = $stmt->fetch()['existe'];
        } while ($existe > 0);
    }
    
    echo json_encode([
        'success' => true,
        'codigo' => $codigo_generado,
        'ultimo_id' => $ultimo_id,
        'siguiente_id' => $siguiente_id,
        'debug' => [
            'metodo' => 'MAX(id) + 1',
            'tabla' => 'productos'
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'codigo' => 'PROD-0000001' // Código por defecto en caso de error
    ]);
}
?>
