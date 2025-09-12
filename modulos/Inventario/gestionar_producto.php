<?php
require_once '../../config/config.php';

// Iniciar sesión y verificar permisos (si es necesario)
iniciarSesionSegura();
requireLogin('../../login.php');

header('Content-Type: application/json; charset=UTF-8');

// Obtener los datos de la solicitud
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validar que los datos necesarios están presentes
if (!$data || !isset($data['accion']) || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Datos de solicitud inválidos.']);
    exit;
}

$accion = $data['accion'];
$id_producto = intval($data['id']);

if ($id_producto <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de producto no válido.']);
    exit;
}

try {
    $pdo = conectarDB();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    

    // --- COMPROBACIÓN DE PEDIDOS PENDIENTES ---
    // Solo para inactivar y eliminar, no para reactivar
    if ($accion === 'inactivar' || $accion === 'eliminar') {
        $sql_check = "SELECT COUNT(*) 
                      FROM pedido_detalles pd
                      JOIN pedidos pe ON pd.pedido_id = pe.id
                      WHERE pd.producto_id = :id_producto AND pe.estado = 'pendiente'";
        
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $stmt_check->execute();
        $pedidos_pendientes = $stmt_check->fetchColumn();

        if ($pedidos_pendientes > 0) {
            // Si hay pedidos pendientes, no permitimos la acción
            $mensaje_error = "No se puede " . ($accion === 'eliminar' ? 'eliminar' : 'inactivar') . " el producto porque tiene {$pedidos_pendientes} pedido(s) pendiente(s).";
            echo json_encode(['success' => false, 'message' => $mensaje_error]);
            exit;
        }
    }

    // --- COMPROBACIÓN DE STOCK PARA INACTIVAR ---
    if ($accion === 'inactivar') {
        $sql_stock = "SELECT stock FROM productos WHERE id = :id_producto";
        $stmt_stock = $pdo->prepare($sql_stock);
        $stmt_stock->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $stmt_stock->execute();
        $stock_actual = $stmt_stock->fetchColumn();

        if ($stock_actual > 0) {
            echo json_encode(['success' => false, 'message' => 'No se puede inactivar el producto porque tiene stock disponible.']);
            exit;
        }
    }
    // --- FIN DE LA COMPROBACIÓN DE STOCK ---

    // Procedemos con la acción solicitada
    
    switch ($accion) {
        case 'inactivar':
            $sql = "UPDATE productos SET activo = 0, fecha_modificacion = NOW() WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id_producto, PDO::PARAM_INT);

            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Producto inactivado correctamente.']);
            break;

        case 'reactivar':
            $sql = "UPDATE productos SET activo = 1, fecha_modificacion = NOW() WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id_producto, PDO::PARAM_INT);

            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Producto reactivado correctamente.']);
            break;

        case 'eliminar':
            $sql = "DELETE FROM productos WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id_producto, PDO::PARAM_INT);

            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Producto eliminado permanentemente.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
            break;
    }

} catch (PDOException $e) {
    // Capturar errores de la base de datos
    error_log("Error en gestionar_producto.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Capturar otros errores
    error_log("Error general en gestionar_producto.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error inesperado.']);
}
?>