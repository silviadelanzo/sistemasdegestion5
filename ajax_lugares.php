<?php
// La ruta correcta al archivo de configuración desde la raíz del proyecto.
require_once 'config/config.php';

iniciarSesionSegura();
if (!isset($_SESSION['id_usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
mb_internal_encoding('UTF-8');

$response = ['success' => false, 'message' => 'Acción no válida.'];


$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['accion']) && $input['accion'] === 'crear_simple') {
    $nombre_lugar = trim($input['nombre_lugar'] ?? '');

    if (empty($nombre_lugar)) {
        $response['message'] = 'El nombre de la ubicación no puede estar vacío.';
    } else {
        try {
            $pdo = conectarDB();
            $pdo->exec("SET NAMES utf8mb4");

            $stmt_check = $pdo->prepare("SELECT id FROM lugares WHERE nombre = ? AND activo = 1");
            $stmt_check->execute([$nombre_lugar]);
            if ($stmt_check->fetch()) {
                $response['message'] = 'La ubicación ya existe.';
            } else {
                $sql = "INSERT INTO lugares (nombre, activo, fecha_creacion) VALUES (?, 1, NOW())";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([$nombre_lugar])) {
                    $new_id = $pdo->lastInsertId();
                    $response = [
                        'success' => true,
                        'message' => 'Ubicación creada correctamente.',
                        'lugar' => ['id' => $new_id, 'nombre' => $nombre_lugar]
                    ];
                } else {
                    $response['message'] = 'Error al guardar la ubicación.';
                }
            }
        } catch (PDOException $e) {
            error_log('Error en ajax_lugares.php: ' . $e->getMessage());
            $response['message'] = 'Error de base de datos.';
        }
    }
}

echo json_encode($response);
?>