<?php
require_once '../config/config.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    // Verificar sesión
    iniciarSesionSegura();
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('No autorizado');
    }
    
    $pdo = conectarDB();
    
    // Obtener datos JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (empty($data['nombre'])) {
        throw new Exception('El nombre del impuesto es obligatorio');
    }
    
    if (!isset($data['porcentaje']) || $data['porcentaje'] < 0 || $data['porcentaje'] > 100) {
        throw new Exception('El porcentaje debe estar entre 0 y 100');
    }
    
    $nombre = trim($data['nombre']);
    $porcentaje = floatval($data['porcentaje']);
    
    // Verificar que no existe
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM impuestos WHERE nombre = ?");
    $stmt->execute([$nombre]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Ya existe un impuesto con ese nombre');
    }
    
    // Insertar nuevo impuesto
    $stmt = $pdo->prepare("INSERT INTO impuestos (nombre, porcentaje) VALUES (?, ?)");
    $stmt->execute([$nombre, $porcentaje]);
    
    $nuevo_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'id' => $nuevo_id,
        'nombre' => $nombre,
        'porcentaje' => $porcentaje,
        'message' => 'Impuesto creado exitosamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
