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
        throw new Exception('El nombre del lugar es obligatorio');
    }
    
    $nombre = trim($data['nombre']);
    
    // Verificar que no existe
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lugares WHERE nombre = ?");
    $stmt->execute([$nombre]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Ya existe un lugar con ese nombre');
    }
    
    // Insertar nuevo lugar
    $stmt = $pdo->prepare("INSERT INTO lugares (nombre) VALUES (?)");
    $stmt->execute([$nombre]);
    
    $nuevo_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'id' => $nuevo_id,
        'nombre' => $nombre,
        'message' => 'Lugar creado exitosamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
