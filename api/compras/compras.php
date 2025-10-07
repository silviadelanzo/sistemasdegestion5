<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    $method = $_SERVER['REQUEST_METHOD'];
    $response_data = [];

    if ($method === 'GET') {
        $id = $_GET['id'] ?? 0;
        $context = $_GET['context'] ?? '';

        if ($id > 0 || $context === 'form') {
            // --- Devolver datos para el FORMULARIO (nuevo o edición) ---
            $proveedores = $pdo->query("SELECT id, razon_social, condiciones_pago FROM proveedores WHERE activo = 1 ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC);
            $depositos = $pdo->query("SELECT id_deposito, nombre_deposito FROM oc_depositos WHERE activo = 1 ORDER BY nombre_deposito")->fetchAll(PDO::FETCH_ASSOC);
            $estados = $pdo->query("SELECT id_estado, nombre_estado FROM oc_estados ORDER BY id_estado")->fetchAll(PDO::FETCH_ASSOC);
            
            $response_data = [
                'proveedores' => $proveedores,
                'depositos' => $depositos,
                'estados' => $estados,
                'compra' => null,
                'detalles' => [],
                'nuevo_numero_oc' => ''
            ];

            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT * FROM oc_ordenes WHERE id_orden = ?");
                $stmt->execute([$id]);
                $response_data['compra'] = $stmt->fetch(PDO::FETCH_ASSOC);

                $stmt_detalles = $pdo->prepare("SELECT cd.*, p.nombre, p.codigo FROM oc_detalle cd JOIN productos p ON cd.producto_id = p.id WHERE cd.id_orden = ?");
                $stmt_detalles->execute([$id]);
                $response_data['detalles'] = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt_oc = $pdo->query("SELECT numero_orden FROM oc_ordenes ORDER BY id_orden DESC LIMIT 1");
                $ultimo_oc = $stmt_oc->fetchColumn();
                $numero = $ultimo_oc ? intval(substr($ultimo_oc, 3)) + 1 : 1;
                $response_data['nuevo_numero_oc'] = 'OC-' . str_pad($numero, 7, '0', STR_PAD_LEFT);
            }

        } else {
            // --- Devolver la lista completa (para la tabla principal) ---
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $per_page = 25;
            $offset = ($page - 1) * $per_page;

            $filtro_busqueda    = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
            $filtro_proveedor   = isset($_GET['proveedor']) ? trim($_GET['proveedor']) : '';
            $filtro_fecha_desde = isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
            $filtro_fecha_hasta = isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

            $orden_campo = isset($_GET['orden']) ? $_GET['orden'] : 'numero_orden';
            $orden_direccion = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
            $campos_permitidos = ['numero_orden', 'proveedor_nombre', 'fecha_orden', 'estado_id'];
            if (!in_array($orden_campo, $campos_permitidos)) { $orden_campo = 'numero_orden'; }

            $params = [];
            $sql_base = "FROM oc_ordenes oc LEFT JOIN proveedores p ON oc.proveedor_id = p.id LEFT JOIN oc_estados es ON oc.estado_id = es.id_estado";
            $where_conditions = [];

            if ($filtro_busqueda !== '') { $where_conditions[] = "(oc.numero_orden LIKE :busqueda OR p.razon_social LIKE :busqueda)"; $params[':busqueda'] = "%{$filtro_busqueda}%"; }
            if ($filtro_proveedor !== '' && $filtro_proveedor !== 'todos') { $where_conditions[] = "oc.proveedor_id = :proveedor"; $params[':proveedor'] = $filtro_proveedor; }
            if ($filtro_fecha_desde !== '') { $where_conditions[] = "oc.fecha_orden >= :fecha_desde"; $params[':fecha_desde'] = $filtro_fecha_desde; }
            if ($filtro_fecha_hasta !== '') { $where_conditions[] = "oc.fecha_orden <= :fecha_hasta"; $params[':fecha_hasta'] = $filtro_fecha_hasta; }

            $where_clause = $where_conditions ? ' WHERE ' . implode(' AND ', $where_conditions) : '';

            $sql = "SELECT oc.id_orden, oc.numero_orden, p.razon_social as proveedor_nombre, oc.fecha_orden, es.nombre_estado, oc.estado_id, oc.total " . $sql_base . $where_clause . " ORDER BY " . ($orden_campo === 'proveedor_nombre' ? 'p.razon_social' : 'oc.' . $orden_campo) . " {$orden_direccion} LIMIT {$per_page} OFFSET {$offset}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $ordenes_compra = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $count_sql = "SELECT COUNT(oc.id_orden) " . $sql_base . $where_clause;
            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute($params);
            $total_records = $count_stmt->fetchColumn();
            $total_pages = ceil($total_records / $per_page);

            $stats_query = $pdo->query("SELECT estado_id, COUNT(id_orden) as count FROM oc_ordenes GROUP BY estado_id");
            $counts_by_id = $stats_query->fetchAll(PDO::FETCH_KEY_PAIR);
            $valor_total = (float) $pdo->query("SELECT COALESCE(SUM(total), 0) FROM oc_ordenes")->fetchColumn();

            $response_data = [
                'ordenes_compra' => $ordenes_compra,
                'paginacion' => ['page' => $page, 'total_pages' => $total_pages, 'total_records' => (int) $total_records],
                'stats' => ['counts' => $counts_by_id, 'valor_total' => $valor_total, 'total' => (int)array_sum($counts_by_id)],
                'proveedores' => $pdo->query("SELECT id, razon_social FROM proveedores WHERE activo = 1 ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC)
            ];
        }
    } elseif ($method === 'POST') {
        // ... Lógica POST ...
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            throw new Exception('ID de orden requerido para eliminar');
        }
        
        // Verificar que la orden existe
        $stmt = $pdo->prepare("SELECT id_orden, numero_orden FROM oc_ordenes WHERE id_orden = ?");
        $stmt->execute([$id]);
        $orden = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$orden) {
            throw new Exception('Orden no encontrada');
        }
        
        $pdo->beginTransaction();
        
        try {
            // 1. Insertar en auditoría ANTES de eliminar
            $stmt_auditoria = $pdo->prepare("
                INSERT INTO auditoria (
                    tabla_afectada, accion, registro_id, detalle, 
                    usuario_id, fecha
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $detalle = json_encode([
                'id_orden' => $orden['id_orden'],
                'numero_orden' => $orden['numero_orden'],
                'fecha_eliminacion' => date('Y-m-d H:i:s'),
                'accion' => 'Orden eliminada por usuario'
            ]);
            
            $stmt_auditoria->execute([
                'oc_ordenes',
                'DELETE',
                $id,
                $detalle,
                $_SESSION['user_id'] ?? 1
            ]);
            
            // 2. Eliminar detalles de la orden
            $stmt_detalle = $pdo->prepare("DELETE FROM oc_detalle WHERE id_orden = ?");
            $stmt_detalle->execute([$id]);
            
            // 3. Eliminar la orden principal
            $stmt_orden = $pdo->prepare("DELETE FROM oc_ordenes WHERE id_orden = ?");
            $stmt_orden->execute([$id]);
            
            $pdo->commit();
            
            $response_data = [
                'message' => "Orden {$orden['numero_orden']} eliminada exitosamente",
                'id_eliminado' => $id
            ];
            
        } catch (Exception $e) {
            $pdo->rollback();
            throw new Exception('Error al eliminar la orden: ' . $e->getMessage());
        }
    }

    echo json_encode(['status' => 'success', 'data' => $response_data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>