<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $prioridad = $_POST['prioridad'] ?? '';
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $completada = isset($_POST['completada']) ? 1 : 0;

    if (empty($titulo) || empty($fecha_inicio) || empty($fecha_fin)) {
        $response['message'] = 'Título, fecha de inicio y fecha de fin son obligatorios.';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo = conectarDB();

        if ($id) {
            // Update existing event
            $sql = "UPDATE agenda SET 
                        titulo = :titulo, 
                        descripcion = :descripcion, 
                        tipo = :tipo, 
                        prioridad = :prioridad, 
                        fecha_inicio = :fecha_inicio, 
                        fecha_fin = :fecha_fin, 
                        completada = :completada 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titulo' => $titulo,
                ':descripcion' => $descripcion,
                ':tipo' => $tipo,
                ':prioridad' => $prioridad,
                ':fecha_inicio' => $fecha_inicio,
                ':fecha_fin' => $fecha_fin,
                ':completada' => $completada,
                ':id' => $id
            ]);
            $response['message'] = 'Evento actualizado correctamente.';
        } else {
            // Insert new event
            $sql = "INSERT INTO agenda (titulo, descripcion, tipo, prioridad, fecha_inicio, fecha_fin, completada) 
                    VALUES (:titulo, :descripcion, :tipo, :prioridad, :fecha_inicio, :fecha_fin, :completada)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titulo' => $titulo,
                ':descripcion' => $descripcion,
                ':tipo' => $tipo,
                ':prioridad' => $prioridad,
                ':fecha_inicio' => $fecha_inicio,
                ':fecha_fin' => $fecha_fin,
                ':completada' => $completada
            ]);
            $response['message'] = 'Evento guardado correctamente.';
        }

        $response['success'] = true;

    } catch (PDOException $e) {
        $response['message'] = 'Error al guardar el evento: ' . $e->getMessage();
    }
}

echo json_encode($response);

?>