<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'crear_proveedor':
            $codigo = $_POST['codigo'] ?? '';
            $razon_social = $_POST['razon_social'] ?? '';
            $nombre_comercial = $_POST['nombre_comercial'] ?? '';
            $cuit = $_POST['cuit'] ?? '';
            $direccion = $_POST['direccion'] ?? '';
            $pais_id = $_POST['pais_id'] ?: null;
            $provincia_id = $_POST['provincia_id'] ?: null;
            $ciudad_id = $_POST['ciudad_id'] ?: null;
            $telefono = $_POST['telefono'] ?? '';
            $whatsapp = $_POST['whatsapp'] ?? '';
            $email = $_POST['email'] ?? '';
            $sitio_web = $_POST['sitio_web'] ?? '';

            // Formatear teléfonos (agregar +54 si no lo tiene)
            if ($telefono && !str_starts_with($telefono, '+')) {
                $telefono = '+54' . preg_replace('/[^0-9]/', '', $telefono);
            }
            if ($whatsapp && !str_starts_with($whatsapp, '+')) {
                $whatsapp = '+54' . preg_replace('/[^0-9]/', '', $whatsapp);
            }

            // Validaciones
            if (empty($codigo) || empty($razon_social)) {
                throw new Exception('Código y Razón Social son obligatorios');
            }

            // Verificar si el código ya existe
            $stmt = $pdo->prepare("SELECT id FROM proveedores WHERE codigo = ?");
            $stmt->execute([$codigo]);
            if ($stmt->fetch()) {
                throw new Exception('El código de proveedor ya existe');
            }

            // Insertar proveedor
            $sql = "INSERT INTO proveedores (
                codigo, razon_social, nombre_comercial, cuit, direccion, 
                pais_id, provincia_id, ciudad_id, telefono, whatsapp, 
                email, sitio_web, activo, fecha_creacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $codigo,
                $razon_social,
                $nombre_comercial,
                $cuit,
                $direccion,
                $pais_id,
                $provincia_id,
                $ciudad_id,
                $telefono,
                $whatsapp,
                $email,
                $sitio_web
            ]);

            $proveedor_id = $pdo->lastInsertId();

            // Obtener el proveedor creado
            $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = ?");
            $stmt->execute([$proveedor_id]);
            $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

            $response = [
                'success' => true,
                'message' => 'Proveedor creado exitosamente',
                'proveedor' => $proveedor
            ];
            break;

        case 'actualizar_proveedor':
            $id = $_POST['id'] ?? 0;
            $codigo = $_POST['codigo'] ?? '';
            $razon_social = $_POST['razon_social'] ?? '';
            $nombre_comercial = $_POST['nombre_comercial'] ?? '';
            $cuit = $_POST['cuit'] ?? '';
            $direccion = $_POST['direccion'] ?? '';
            $pais_id = $_POST['pais_id'] ?: null;
            $provincia_id = $_POST['provincia_id'] ?: null;
            $ciudad_id = $_POST['ciudad_id'] ?: null;
            $telefono = $_POST['telefono'] ?? '';
            $whatsapp = $_POST['whatsapp'] ?? '';
            $email = $_POST['email'] ?? '';
            $sitio_web = $_POST['sitio_web'] ?? '';

            // Formatear teléfonos
            if ($telefono && !str_starts_with($telefono, '+')) {
                $telefono = '+54' . preg_replace('/[^0-9]/', '', $telefono);
            }
            if ($whatsapp && !str_starts_with($whatsapp, '+')) {
                $whatsapp = '+54' . preg_replace('/[^0-9]/', '', $whatsapp);
            }

            if (empty($id) || empty($codigo) || empty($razon_social)) {
                throw new Exception('ID, Código y Razón Social son obligatorios');
            }

            // Verificar si el código ya existe en otro proveedor
            $stmt = $pdo->prepare("SELECT id FROM proveedores WHERE codigo = ? AND id != ?");
            $stmt->execute([$codigo, $id]);
            if ($stmt->fetch()) {
                throw new Exception('El código de proveedor ya existe');
            }

            $sql = "UPDATE proveedores SET 
                codigo = ?, razon_social = ?, nombre_comercial = ?, cuit = ?, 
                direccion = ?, pais_id = ?, provincia_id = ?, ciudad_id = ?, 
                telefono = ?, whatsapp = ?, email = ?, sitio_web = ?, 
                fecha_modificacion = NOW()
                WHERE id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $codigo,
                $razon_social,
                $nombre_comercial,
                $cuit,
                $direccion,
                $pais_id,
                $provincia_id,
                $ciudad_id,
                $telefono,
                $whatsapp,
                $email,
                $sitio_web,
                $id
            ]);

            $response = [
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente'
            ];
            break;

        case 'eliminar_proveedor':
            $id = $_POST['id'] ?? 0;

            if (empty($id)) {
                throw new Exception('ID del proveedor es obligatorio');
            }

            // Verificar si el proveedor tiene compras asociadas
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM compras WHERE proveedor_id = ?");
            $stmt->execute([$id]);
            $compras = $stmt->fetchColumn();

            if ($compras > 0) {
                // Desactivar en lugar de eliminar
                $stmt = $pdo->prepare("UPDATE proveedores SET activo = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $response = [
                    'success' => true,
                    'message' => 'Proveedor desactivado (tiene compras asociadas)'
                ];
            } else {
                // Eliminar físicamente
                $stmt = $pdo->prepare("DELETE FROM proveedores WHERE id = ?");
                $stmt->execute([$id]);
                $response = [
                    'success' => true,
                    'message' => 'Proveedor eliminado exitosamente'
                ];
            }
            break;

        case 'activar_proveedor':
            $id = $_POST['id'] ?? 0;

            if (empty($id)) {
                throw new Exception('ID del proveedor es obligatorio');
            }

            $stmt = $pdo->prepare("UPDATE proveedores SET activo = 1 WHERE id = ?");
            $stmt->execute([$id]);

            $response = [
                'success' => true,
                'message' => 'Proveedor activado exitosamente'
            ];
            break;

        case 'obtener_proveedor':
            $id = $_GET['id'] ?? 0;

            if (empty($id)) {
                throw new Exception('ID del proveedor es obligatorio');
            }

            $stmt = $pdo->prepare("
                SELECT p.*, 
                       pa.nombre as pais_nombre,
                       pr.nombre as provincia_nombre,
                       c.nombre as ciudad_nombre
                FROM proveedores p
                LEFT JOIN paises pa ON p.pais_id = pa.id
                LEFT JOIN provincias pr ON p.provincia_id = pr.id
                LEFT JOIN ciudades c ON p.ciudad_id = c.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$proveedor) {
                throw new Exception('Proveedor no encontrado');
            }

            $response = [
                'success' => true,
                'proveedor' => $proveedor
            ];
            break;

        case 'buscar_proveedores':
            $busqueda = $_GET['q'] ?? '';
            $limit = $_GET['limit'] ?? 10;

            $sql = "SELECT id, codigo, razon_social, nombre_comercial, telefono, whatsapp, email
                    FROM proveedores 
                    WHERE activo = 1";

            $params = [];

            if (!empty($busqueda)) {
                $sql .= " AND (razon_social LIKE ? OR nombre_comercial LIKE ? OR codigo LIKE ?)";
                $busquedaParam = "%$busqueda%";
                $params = [$busquedaParam, $busquedaParam, $busquedaParam];
            }

            $sql .= " ORDER BY razon_social LIMIT ?";
            $params[] = (int)$limit;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = [
                'success' => true,
                'proveedores' => $proveedores
            ];
            break;

        case 'crear_pais':
            $nombre = $_POST['nombre'] ?? '';
            $codigo = $_POST['codigo'] ?? '';

            if (empty($nombre)) {
                throw new Exception('Nombre del país es obligatorio');
            }

            $stmt = $pdo->prepare("INSERT INTO paises (nombre, codigo, activo) VALUES (?, ?, 1)");
            $stmt->execute([$nombre, $codigo]);

            $pais_id = $pdo->lastInsertId();

            $response = [
                'success' => true,
                'message' => 'País creado exitosamente',
                'id' => $pais_id,
                'nombre' => $nombre
            ];
            break;

        case 'crear_provincia':
            $nombre = $_POST['nombre'] ?? '';
            $pais_id = $_POST['pais_id'] ?? null;

            if (empty($nombre)) {
                throw new Exception('Nombre de la provincia es obligatorio');
            }

            $stmt = $pdo->prepare("INSERT INTO provincias (nombre, pais_id, activo) VALUES (?, ?, 1)");
            $stmt->execute([$nombre, $pais_id]);

            $provincia_id = $pdo->lastInsertId();

            $response = [
                'success' => true,
                'message' => 'Provincia creada exitosamente',
                'id' => $provincia_id,
                'nombre' => $nombre
            ];
            break;

        case 'crear_ciudad':
            $nombre = $_POST['nombre'] ?? '';
            $provincia_id = $_POST['provincia_id'] ?? null;

            if (empty($nombre)) {
                throw new Exception('Nombre de la ciudad es obligatorio');
            }

            $stmt = $pdo->prepare("INSERT INTO ciudades (nombre, provincia_id, activo) VALUES (?, ?, 1)");
            $stmt->execute([$nombre, $provincia_id]);

            $ciudad_id = $pdo->lastInsertId();

            $response = [
                'success' => true,
                'message' => 'Ciudad creada exitosamente',
                'id' => $ciudad_id,
                'nombre' => $nombre
            ];
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
} catch (PDOException $e) {
    $response = [
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ];
}

echo json_encode($response);
