<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

// Permitir tanto POST como GET para compatibilidad
$method = $_SERVER['REQUEST_METHOD'];
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Si es AJAX, responder JSON. Si no, usar redirect
if ($is_ajax) {
    header('Content-Type: application/json');
}

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Verificar si las columnas de papelera existen, si no crearlas
    $stmt = $pdo->query("SHOW COLUMNS FROM proveedores LIKE 'eliminado'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE proveedores ADD COLUMN eliminado TINYINT(1) DEFAULT 0");
        $pdo->exec("ALTER TABLE proveedores ADD COLUMN fecha_eliminacion DATETIME NULL");
        $pdo->exec("ALTER TABLE proveedores ADD COLUMN eliminado_por VARCHAR(100) NULL");
    }
    
    // Obtener datos según el método
    if ($method === 'POST') {
        if ($is_ajax) {
            $data = json_decode(file_get_contents('php://input'), true);
        } else {
            $data = $_POST;
        }
    } else {
        $data = $_GET;
    }
    
    $accion = $data['accion'] ?? '';
    $proveedor_id = $data['id'] ?? 0;
    $usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Sistema';
    
    switch ($accion) {
        case 'crear':
            // Verificar que no exista la razón social
            $stmt = $pdo->prepare("SELECT id FROM proveedores WHERE razon_social = ? AND eliminado = 0");
            $stmt->execute([$data['razon_social']]);
            if ($stmt->fetch()) {
                throw new Exception('Ya existe un proveedor con esa razón social');
            }
            
            $codigo = generarCodigoProveedor($pdo);
            $stmt = $pdo->prepare("
                INSERT INTO proveedores (codigo, razon_social, nombre_comercial, cuit, direccion, 
                                       ciudad, provincia, codigo_postal, telefono, email, 
                                       contacto_nombre, contacto_telefono, contacto_email, 
                                       condiciones_pago, dias_entrega, activo, eliminado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0)
            ");
            $stmt->execute([
                $codigo,
                $data['razon_social'],
                $data['nombre_comercial'] ?? null,
                $data['cuit'] ?? null,
                $data['direccion'] ?? null,
                $data['ciudad'] ?? null,
                $data['provincia'] ?? null,
                $data['codigo_postal'] ?? null,
                $data['telefono'] ?? null,
                $data['email'] ?? null,
                $data['contacto_nombre'] ?? null,
                $data['contacto_telefono'] ?? null,
                $data['contacto_email'] ?? null,
                $data['condiciones_pago'] ?? null,
                $data['dias_entrega'] ?? 0
            ]);
            
            $mensaje = 'Proveedor creado exitosamente con código: ' . $codigo;
            $success = true;
            break;
            
        case 'editar':
            $stmt = $pdo->prepare("
                UPDATE proveedores 
                SET razon_social = ?, nombre_comercial = ?, cuit = ?, direccion = ?, 
                    ciudad = ?, provincia = ?, codigo_postal = ?, telefono = ?, email = ?, 
                    contacto_nombre = ?, contacto_telefono = ?, contacto_email = ?, 
                    condiciones_pago = ?, dias_entrega = ?
                WHERE id = ? AND eliminado = 0
            ");
            $stmt->execute([
                $data['razon_social'],
                $data['nombre_comercial'] ?? null,
                $data['cuit'] ?? null,
                $data['direccion'] ?? null,
                $data['ciudad'] ?? null,
                $data['provincia'] ?? null,
                $data['codigo_postal'] ?? null,
                $data['telefono'] ?? null,
                $data['email'] ?? null,
                $data['contacto_nombre'] ?? null,
                $data['contacto_telefono'] ?? null,
                $data['contacto_email'] ?? null,
                $data['condiciones_pago'] ?? null,
                $data['dias_entrega'] ?? 0,
                $proveedor_id
            ]);
            
            $mensaje = 'Proveedor actualizado exitosamente';
            $success = true;
            break;
            
        case 'cambiar_estado':
            // Cambiar entre activo/inactivo (NO eliminar)
            $stmt = $pdo->prepare("SELECT activo FROM proveedores WHERE id = ? AND eliminado = 0");
            $stmt->execute([$proveedor_id]);
            $proveedor = $stmt->fetch();
            
            if (!$proveedor) {
                throw new Exception('Proveedor no encontrado');
            }
            
            $nuevo_estado = $proveedor['activo'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE proveedores SET activo = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $proveedor_id]);
            
            $mensaje = $nuevo_estado ? 'Proveedor activado exitosamente' : 'Proveedor desactivado exitosamente';
            $success = true;
            break;
            
        case 'eliminar':
            // Mover a papelera (soft delete)
            $stmt = $pdo->prepare("
                UPDATE proveedores 
                SET eliminado = 1, fecha_eliminacion = NOW(), eliminado_por = ? 
                WHERE id = ? AND eliminado = 0
            ");
            $stmt->execute([$usuario_nombre, $proveedor_id]);
            
            if ($stmt->rowCount() > 0) {
                $mensaje = 'Proveedor movido a papelera exitosamente';
                $success = true;
                $redirect_url = 'proveedores.php?msg=eliminado';
            } else {
                throw new Exception('No se pudo mover el proveedor a papelera');
            }
            break;
            
        case 'restaurar':
            // Restaurar desde papelera
            $stmt = $pdo->prepare("
                UPDATE proveedores 
                SET eliminado = 0, fecha_eliminacion = NULL, eliminado_por = NULL, activo = 1 
                WHERE id = ? AND eliminado = 1
            ");
            $stmt->execute([$proveedor_id]);
            
            if ($stmt->rowCount() > 0) {
                $mensaje = 'Proveedor restaurado exitosamente';
                $success = true;
                $redirect_url = 'papelera_proveedores.php?msg=restaurado';
            } else {
                throw new Exception('No se pudo restaurar el proveedor');
            }
            break;
            
        case 'eliminar_definitivo':
            // Solo para administradores - eliminar completamente
            $es_admin = ($_SESSION['rol_usuario'] ?? '') === 'admin' || ($_SESSION['rol_usuario'] ?? '') === 'administrador';
            if (!$es_admin) {
                throw new Exception('No tiene permisos para eliminar definitivamente');
            }
            
            // Verificar si tiene compras asociadas
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM compras WHERE proveedor_id = ?");
            $stmt->execute([$proveedor_id]);
            $compras = $stmt->fetch();
            
            if ($compras['total'] > 0) {
                throw new Exception('No se puede eliminar definitivamente porque tiene compras asociadas');
            }
            
            $stmt = $pdo->prepare("DELETE FROM proveedores WHERE id = ? AND eliminado = 1");
            $stmt->execute([$proveedor_id]);
            
            if ($stmt->rowCount() > 0) {
                $mensaje = 'Proveedor eliminado definitivamente';
                $success = true;
                $redirect_url = 'papelera_proveedores.php?msg=eliminado_definitivo';
            } else {
                throw new Exception('No se pudo eliminar definitivamente');
            }
            break;
            
        case 'crear_proveedor':
            // Acción para el modal AJAX
            // Verificar que no exista la razón social
            $stmt = $pdo->prepare("SELECT id FROM proveedores WHERE razon_social = ? AND eliminado = 0");
            $stmt->execute([$data['razon_social']]);
            if ($stmt->fetch()) {
                throw new Exception('Ya existe un proveedor con esa razón social');
            }
            
            // Generar código automático
            $codigo = $data['codigo'] ?? '';
            if (empty($codigo)) {
                $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) as max_num FROM proveedores WHERE codigo LIKE 'PROV%'");
                $max_num = $stmt->fetch()['max_num'] ?? 0;
                $codigo = 'PROV' . str_pad($max_num + 1, 3, '0', STR_PAD_LEFT);
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO proveedores (codigo, razon_social, nombre_comercial, cuit, direccion, telefono, whatsapp, email, sitio_web, activo, fecha_creacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([
                $codigo,
                $data['razon_social'],
                $data['nombre_comercial'] ?? '',
                $data['cuit'] ?? '',
                $data['direccion'] ?? '',
                $data['telefono'] ?? '',
                $data['whatsapp'] ?? '',
                $data['email'] ?? '',
                $data['sitio_web'] ?? ''
            ]);
            
            $proveedor_id = $pdo->lastInsertId();
            $mensaje = 'Proveedor creado exitosamente';
            $success = true;
            
            // Respuesta para AJAX
            if ($is_ajax) {
                echo json_encode([
                    'success' => true,
                    'message' => $mensaje,
                    'proveedor' => [
                        'id' => $proveedor_id,
                        'codigo' => $codigo,
                        'razon_social' => $data['razon_social']
                    ]
                ]);
                exit;
            }
            break;
            
        case 'actualizar_proveedor':
            // Acción para editar desde el modal AJAX
            // Verificar que no exista otra razón social igual (excepto la actual)
            $stmt = $pdo->prepare("SELECT id FROM proveedores WHERE razon_social = ? AND id != ? AND eliminado = 0");
            $stmt->execute([$data['razon_social'], $proveedor_id]);
            if ($stmt->fetch()) {
                throw new Exception('Ya existe otro proveedor con esa razón social');
            }
            
            $stmt = $pdo->prepare("
                UPDATE proveedores SET 
                    razon_social = ?, nombre_comercial = ?, cuit = ?, direccion = ?, 
                    telefono = ?, whatsapp = ?, email = ?, sitio_web = ?
                WHERE id = ? AND eliminado = 0
            ");
            $stmt->execute([
                $data['razon_social'],
                $data['nombre_comercial'] ?? '',
                $data['cuit'] ?? '',
                $data['direccion'] ?? '',
                $data['telefono'] ?? '',
                $data['whatsapp'] ?? '',
                $data['email'] ?? '',
                $data['sitio_web'] ?? '',
                $proveedor_id
            ]);
            
            $mensaje = 'Proveedor actualizado exitosamente';
            $success = true;
            
            // Respuesta para AJAX
            if ($is_ajax) {
                echo json_encode([
                    'success' => true,
                    'message' => $mensaje
                ]);
                exit;
            }
            break;
            
        case 'obtener_proveedor':
            // Obtener datos de un proveedor para editar
            $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = ? AND eliminado = 0");
            $stmt->execute([$proveedor_id]);
            $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($proveedor) {
                echo json_encode([
                    'success' => true,
                    'proveedor' => $proveedor
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Proveedor no encontrado'
                ]);
            }
            exit;
            break;
            
        default:
            throw new Exception('Acción no válida: ' . $accion);
    }

} catch (Exception $e) {
    $mensaje = 'Error: ' . $e->getMessage();
    $success = false;
}

// Responder según el tipo de request
if ($is_ajax) {
    echo json_encode([
        'success' => $success ?? false, 
        'message' => $mensaje ?? 'Error desconocido',
        'redirect' => $redirect_url ?? null
    ]);
} else {
    // Redirect con mensaje
    $redirect = $redirect_url ?? 'proveedores.php';
    if ($success ?? false) {
        $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . 'success=' . urlencode($mensaje);
    } else {
        $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . 'error=' . urlencode($mensaje);
    }
    header('Location: ' . $redirect);
    exit;
}

function generarCodigoProveedor($pdo) {
    // Buscar el último código numérico
    $stmt = $pdo->query("SELECT codigo FROM proveedores WHERE codigo LIKE 'PROV%' ORDER BY CAST(SUBSTRING(codigo, 5) AS UNSIGNED) DESC LIMIT 1");
    $ultimo = $stmt->fetch();
    
    if ($ultimo) {
        $numero = intval(substr($ultimo['codigo'], 4)) + 1;
    } else {
        $numero = 1;
    }
    
    return 'PROV' . str_pad($numero, 3, '0', STR_PAD_LEFT);
}
?>