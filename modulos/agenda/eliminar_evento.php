<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;

    if ($id) {
        try {
            $pdo = conectarDB();
            $sql = "DELETE FROM agenda WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$id])) {
                echo json_encode(['success' => true, 'message' => 'Evento eliminado correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar el evento.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No se proporcionó el ID del evento.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>