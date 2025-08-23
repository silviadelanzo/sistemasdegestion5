<?php
// RUTA CORREGIDA: Sube dos niveles desde /modulos/Inventario/ para encontrar config.php
require_once '../../config/config.php';

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
    $nombre_categoria = trim($input['nombre_categoria'] ?? '');

    if (empty($nombre_categoria)) {
        $response['message'] = 'El nombre de la categoría no puede estar vacío.';
    } else {
        try {
            $pdo = conectarDB();
            $pdo->exec("SET NAMES utf8mb4");

            $stmt_check = $pdo->prepare("SELECT id FROM categorias WHERE nombre = ? AND activo = 1");
            $stmt_check->execute([$nombre_categoria]);
            if ($stmt_check->fetch()) {
                $response['message'] = 'La categoría ya existe.';
            } else {
                $sql = "INSERT INTO categorias (nombre, activo, fecha_creacion) VALUES (?, 1, NOW())";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([$nombre_categoria])) {
                    $new_id = $pdo->lastInsertId();
                    $response = [
                        'success' => true,
                        'message' => 'Categoría creada correctamente.',
                        'categoria' => ['id' => $new_id, 'nombre' => $nombre_categoria]
                    ];
                } else {
                    $response['message'] = 'Error al guardar la categoría.';
                }
            }
        } catch (PDOException $e) {
            error_log('Error en ajax_categorias.php: ' . $e->getMessage());
            $response['message'] = 'Error de base de datos.';
        }
    }
}

echo json_encode($response);
?>