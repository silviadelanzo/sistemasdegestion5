<?php
header('Content-Type: application/json');

require_once '../../config/config.php';

try {
    $pdo = conectarDB();
    
    // Por ahora, traemos todos los eventos. Se puede añadir filtro por usuario si es necesario.
    $stmt = $pdo->prepare("SELECT id, titulo, descripcion, fecha_inicio, fecha_fin, tipo, prioridad, completada FROM agenda");
    $stmt->execute();
    
    $eventos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $color = '#007bff'; // Color por defecto para evento
        if ($row['tipo'] === 'tarea') {
            $color = '#17a2b8'; // Azul claro para tarea
        } elseif ($row['tipo'] === 'alerta') {
            $color = '#ffc107'; // Amarillo para alerta
        }
        
        if ($row['completada']) {
            $color = '#28a745'; // Verde para completada
        }

        $eventos[] = [
            'id'    => $row['id'],
            'title' => $row['titulo'],
            'start' => $row['fecha_inicio'],
            'end'   => $row['fecha_fin'],
            'color' => $color,
            'extendedProps' => [
                'descripcion' => $row['descripcion'],
                'tipo' => $row['tipo'],
                'prioridad' => $row['prioridad'],
                'completada' => $row['completada']
            ]
        ];
    }
    
    echo json_encode($eventos);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener los eventos: ' . $e->getMessage()]);
}
