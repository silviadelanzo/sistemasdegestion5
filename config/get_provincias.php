<?php
require_once 'config.php';
header('Content-Type: application/json; charset=UTF-8');

try {
    $pdo = conectarDB();
    $pais_id = $_GET['pais_id'] ?? 0;
    
    if ($pais_id > 0) {
        // Buscar provincias del país específico
        $stmt = $pdo->prepare("SELECT id, nombre FROM provincias WHERE pais_id = ? ORDER BY nombre");
        $stmt->execute([$pais_id]);
        $provincias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Si no hay pais_id, buscar todas las provincias argentinas por defecto
        $stmt = $pdo->prepare("
            SELECT p.id, p.nombre 
            FROM provincias p 
            INNER JOIN paises pa ON p.pais_id = pa.id 
            WHERE pa.nombre LIKE '%Argentina%' 
            ORDER BY p.nombre
        ");
        $stmt->execute();
        $provincias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($provincias);
    
} catch (Exception $e) {
    // En caso de error, devolver array vacío
    echo json_encode([]);
}
?>
