<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin();
header('Content-Type: application/json; charset=UTF-8');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$response = ['success' => false, 'message' => 'Solicitud inválida.'];

if (!$data && isset($_GET['accion']) && isset($_GET['id'])) {
    $data = $_GET;
}

if ($data && isset($data['accion']) && isset($data['id'])) {
    $accion = $data['accion'];
    $id = intval($data['id']);

    if ($id <= 0) {
        $response['message'] = 'ID de proveedor inválido.';
    } else {
        try {
            $pdo = conectarDB();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

            $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = ?");
            $stmt->execute([$id]);
            $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$proveedor) {
                $response['message'] = 'Proveedor no encontrado.';
            } else {
                switch ($accion) {
                    case 'inactivar':
                        if ($proveedor['activo'] == 0) {
                            $response['message'] = 'El proveedor ya está inactivo.';
                            break;
                        }
                        // Aquí puedes añadir validaciones extra si es necesario
                        $stmt = $pdo->prepare("UPDATE proveedores SET activo = 0, fecha_modificacion = NOW() WHERE id = ?");
                        $stmt->execute([$id]);
                        $response = ['success' => true, 'message' => 'Proveedor inactivado correctamente.'];
                        break;

                    case 'reactivar':
                        if ($proveedor['activo'] == 1) {
                            $response['message'] = 'El proveedor ya está activo.';
                            break;
                        }
                        $stmt = $pdo->prepare("UPDATE proveedores SET activo = 1, fecha_modificacion = NOW() WHERE id = ?");
                        $stmt->execute([$id]);
                        $response = ['success' => true, 'message' => 'Proveedor reactivado correctamente.'];
                        break;

                    case 'eliminar':
                        // Solo permitir eliminar si está inactivo
                        if ($proveedor['activo'] == 1) {
                            $response['message'] = 'No se puede eliminar un proveedor activo. Inactívalo primero.';
                            break;
                        }

                        $errors = [];

                        // Verificar si tiene compras asociadas
                        $stmt_compras = $pdo->prepare("SELECT COUNT(*) FROM oc_ordenes WHERE proveedor_id = ?");
                        $stmt_compras->execute([$id]);
                        $num_compras = $stmt_compras->fetchColumn();
                        if ($num_compras > 0) {
                            $errors[] = 'tiene ' . $num_compras . ' órdenes de compra asociadas';
                        }

                        // Verificar si está asignado a productos
                        $stmt_productos = $pdo->prepare("
                            SELECT COUNT(*) FROM productos_proveedores
                            WHERE proveedor_principal = ?
                               OR proveedor_alternativo01 = ?
                               OR proveedor_alternativo02 = ?
                               OR proveedor_alternativo03 = ?
                               OR proveedor_alternativo04 = ?
                        ");
                        $stmt_productos->execute([$id, $id, $id, $id, $id]);
                        $num_productos = $stmt_productos->fetchColumn();
                        if ($num_productos > 0) {
                            $errors[] = 'está asignado a ' . $num_productos . ' producto(s)';
                        }

                        if (!empty($errors)) {
                            $response['message'] = 'Este proveedor no se puede eliminar porque ' . implode(' y ', $errors) . '.';
                            break;
                        }

                        $stmt = $pdo->prepare("UPDATE proveedores SET eliminado = 1, fecha_eliminacion = NOW(), eliminado_por = ? WHERE id = ?");
                        $stmt->execute([$_SESSION['nombre_usuario'] ?? 'sistema', $id]);
                        $response = ['success' => true, 'message' => 'Proveedor eliminado permanentemente.'];
                        break;

                    default:
                        $response['message'] = 'Acción no reconocida.';
                        break;
                }
            }
        } catch (PDOException $e) {
            error_log("Error de base de datos: " . $e->getMessage());
            $response['message'] = 'Error en la base de datos. ' . $e->getMessage();
        } catch (Exception $e) {
            error_log("Error general: " . $e->getMessage());
            $response['message'] = 'Ocurrió un error. ' . $e->getMessage();
        }
    }
}

echo json_encode($response);
?>