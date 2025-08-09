<?php
require_once 'config.php';
header('Content-Type: application/json; charset=UTF-8');

try {
    $pdo = conectarDB();
    $provincia_id = $_GET['provincia_id'] ?? 0;
    
    if ($provincia_id > 0) {
        // Buscar ciudades de la provincia específica
        $stmt = $pdo->prepare("SELECT id, nombre FROM ciudades WHERE provincia_id = ? ORDER BY nombre");
        $stmt->execute([$provincia_id]);
        $ciudades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Si no hay provincia_id, devolver array vacío
        $ciudades = [];
    }
    
    echo json_encode($ciudades);
    
} catch (Exception $e) {
    // En caso de error, devolver array vacío
    echo json_encode([]);
}
?>
