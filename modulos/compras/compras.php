<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');
header('Content-Type: text/html; charset=UTF-8');

// --- Lógica de Paginación y Filtros para Órdenes de Compra ---
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Filtros
$filtro_busqueda    = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_proveedor   = isset($_GET['proveedor']) ? trim($_GET['proveedor']) : '';
$filtro_fecha_desde = isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

// Ordenamiento
$orden_campo = isset($_GET['orden']) ? $_GET['orden'] : 'numero_orden';
$orden_direccion = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
$campos_permitidos = ['numero_orden', 'proveedor_nombre', 'fecha_orden', 'estado_id'];
if (!in_array($orden_campo, $campos_permitidos)) {
    $orden_campo = 'numero_orden';
}

$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';

$error_message = '';
$debug_output = '';

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // --- Construcción de la consulta con filtros ---
    $params = [];
    $sql_base = "FROM oc_ordenes oc LEFT JOIN proveedores p ON oc.proveedor_id = p.id LEFT JOIN oc_estados es ON oc.estado_id = es.id_estado";
    
    $where_conditions = [];

    if ($filtro_busqueda !== '') {
        $where_conditions[] = "(oc.numero_orden LIKE :busqueda OR p.razon_social LIKE :busqueda)";
        $params[':busqueda'] = "%{$filtro_busqueda}%";
    }
    if ($filtro_proveedor !== '' && $filtro_proveedor !== 'todos') {
        $where_conditions[] = "oc.proveedor_id = :proveedor";
        $params[':proveedor'] = $filtro_proveedor;
    }
    if ($filtro_fecha_desde !== '') {
        $where_conditions[] = "oc.fecha_orden >= :fecha_desde";
        $params[':fecha_desde'] = $filtro_fecha_desde;
    }
    if ($filtro_fecha_hasta !== '') {
        $where_conditions[] = "oc.fecha_orden <= :fecha_hasta";
        $params[':fecha_hasta'] = $filtro_fecha_hasta;
    }

    $where_clause = $where_conditions ? ' WHERE ' . implode(' AND ', $where_conditions) : '';

    // --- Consulta para obtener los datos de oc_ordenes ---
    $sql = "SELECT oc.id_orden, oc.numero_orden, p.razon_social as proveedor_nombre, oc.fecha_orden, es.nombre_estado, oc.estado_id, oc.total "
        . $sql_base
        . $where_clause;

    if ($orden_campo === 'proveedor_nombre') {
        $sql .= " ORDER BY p.razon_social {$orden_direccion} ";
    } else {
        $sql .= " ORDER BY oc.{$orden_campo} {$orden_direccion} ";
    }

    $sql .= " LIMIT {$per_page} OFFSET {$offset}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ordenes_compra = $stmt->fetchAll();

    // --- Conteo total de oc_ordenes ---
    $count_sql = "SELECT COUNT(oc.id_orden) " . $sql_base . $where_clause;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $ordenes_compra_filtradas = $count_stmt->fetchColumn();
    $total_pages = $ordenes_compra_filtradas > 0 ? ceil($ordenes_compra_filtradas / $per_page) : 1;

    // --- Datos para tarjetas y filtros (actualizados para oc_ordenes) ---
    $estados_raw = $pdo->query("SELECT id_estado, nombre_estado FROM oc_estados")->fetchAll(PDO::FETCH_ASSOC);
    $map_estados = [];
    foreach ($estados_raw as $estado) {
        $map_estados[trim(strtolower($estado['nombre_estado']))] = $estado['id_estado'];
    }

    // IDs para cada estado que nos interesa, usando los nombres correctos de la DB
    $id_pendiente   = $map_estados['pendiente de entrega'] ?? 0;
    $id_parcial     = $map_estados['parcialmente entregada'] ?? 0;
    $id_entregada   = $map_estados['entregada'] ?? 0;
    $id_cancelada   = $map_estados['cancelada'] ?? 0;

    // Se obtienen todos los conteos en una sola consulta para mayor eficiencia
    $counts_query = $pdo->query("SELECT estado_id, COUNT(id_orden) as count FROM oc_ordenes GROUP BY estado_id");
    $counts_by_id = $counts_query->fetchAll(PDO::FETCH_KEY_PAIR);

    $total_ordenes_compra = (int) array_sum($counts_by_id);
    $ordenes_pendientes   = (int) ($counts_by_id[$id_pendiente] ?? 0);
    $ordenes_parcial      = (int) ($counts_by_id[$id_parcial] ?? 0);
    $ordenes_entregadas   = (int) ($counts_by_id[$id_entregada] ?? 0);
    $ordenes_canceladas   = (int) ($counts_by_id[$id_cancelada] ?? 0);
    $valor_total_ordenes_compra  = (float) $pdo->query("SELECT COALESCE(SUM(total), 0) FROM oc_ordenes")->fetchColumn();
    $proveedores = $pdo->query("SELECT id, razon_social FROM proveedores WHERE activo = 1 ORDER BY razon_social")->fetchAll();
} catch (Exception $e) {
    $error_message = "Error al cargar datos: " . $e->getMessage();
    $ordenes_compra = [];
    $total_pages = 1;
    $total_ordenes_compra = 0;
    $valor_total_ordenes_compra = 0;
    $proveedores = [];
    $ordenes_pendientes = 0;
    $ordenes_parcial = 0;
    $ordenes_entregadas = 0;
    $ordenes_canceladas = 0;
}

$pageTitle = "Gestión de Compras - " . SISTEMA_NOMBRE;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .main-container { max-width: 1000px; margin: 0 auto; }
        .table-container, .search-section { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-top: 16px; padding: 10px 12px; }
        .info-cards { display: flex; gap: 8px; margin: 12px 0 6px 0; }
        .info-card { flex: 1; display: flex; align-items: center; padding: 6px 10px; border-radius: 8px; color: #fff; font-weight: 500; font-size: 0.85rem; line-height: 1.1; min-height: 36px; box-shadow: 0 1px 5px rgba(0,0,0,0.04); }
        .info-card .icon { font-size: 1.2rem; margin-left: auto; opacity: 0.65; }
        .ic-blue   { background: linear-gradient(90deg, #396afc 0%, #2948ff 100%); }
        .ic-yellow { background: linear-gradient(90deg, #fc4a1a 0%, #f7b733 100%); }
        .ic-green  { background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%); }
        .ic-cyan   { background: linear-gradient(90deg, #a7a7a7 0%, #636363 100%); }
        .ic-purple { background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); }
        .ic-danger { background: linear-gradient(90deg, #d31027 0%, #ea384d 100%); }
        .table { border-collapse: separate; border-spacing: 0; font-size: 0.92rem; }
        .table thead th { border-top: none !important; border-bottom: 1px solid #e9ecef !important; padding-top: 6px; padding-bottom: 6px; white-space: nowrap; }
        .table tbody tr { border-top: none !important; border-bottom: 1px solid #e9ecef; }
        .table tbody tr:last-child { border-bottom: none; }
        .table tbody td { border-top: none !important; border-bottom: none !important; padding: 6px 8px; vertical-align: middle; }
    </style>
</head>
<body>
    <?php include "../../config/navbar_code.php"; ?>
    <div class="main-container">
        <?php if (!empty($error_message)):
            echo '<div class="alert alert-danger"><strong>Error de base de datos:</strong> ' . htmlspecialchars($error_message) . '</div>';
        endif; ?>
        <!-- Tarjetas resumen -->
        <div class="info-cards">
            <div class="info-card ic-yellow">
                <div>
                    <div style="font-size:0.8rem;font-weight:400;">Pendientes</div>
                    <div style="font-size:1.1rem; font-weight:bold;"><?= $ordenes_pendientes ?> de <?= $total_ordenes_compra ?></div>
                </div>
                <span class="icon ms-2"><i class="bi bi-clock"></i></span>
            </div>
            <div class="info-card ic-blue">
                <div>
                    <div style="font-size:0.8rem;font-weight:400;">Parcialmente Entregada</div>
                    <div style="font-size:1.1rem; font-weight:bold;"><?= $ordenes_parcial ?> de <?= $total_ordenes_compra ?></div>
                </div>
                <span class="icon ms-2"><i class="bi bi-box-seam"></i></span>
            </div>
            <div class="info-card ic-green">
                <div>
                    <div style="font-size:0.8rem;font-weight:400;">Entregadas</div>
                    <div style="font-size:1.1rem; font-weight:bold;"><?= $ordenes_entregadas ?> de <?= $total_ordenes_compra ?></div>
                </div>
                <span class="icon ms-2"><i class="bi bi-check-circle"></i></span>
            </div>
            <div class="info-card ic-danger">
                <div>
                    <div style="font-size:0.8rem;font-weight:400;">Canceladas</div>
                    <div style="font-size:1.1rem; font-weight:bold;"><?= $ordenes_canceladas ?> de <?= $total_ordenes_compra ?></div>
                </div>
                <span class="icon ms-2"><i class="bi bi-x-circle"></i></span>
            </div>
            <div class="info-card ic-purple">
                 <div>
                    <div style="font-size:0.8rem;font-weight:400;">Valor Total</div>
                    <div style="font-size:1.1rem; font-weight:bold;">$<?= number_format($valor_total_ordenes_compra, 2) ?></div>
                </div>
                <span class="icon ms-2"><i class="bi bi-currency-dollar"></i></span>
            </div>
        </div>
        <!-- Buscador y Filtros -->
        <div class="search-section">
            <form method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label for="busqueda" class="form-label mb-1">Nro. Orden o Proveedor</label>
                        <input type="text" class="form-control" id="busqueda" name="busqueda" placeholder="Buscar..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="proveedor" class="form-label mb-1">Proveedor</label>
                        <select class="form-select" id="proveedor" name="proveedor">
                            <option value="todos">Todos</option>
                            <?php foreach ($proveedores as $proveedor):
                                echo '<option value="' . $proveedor['id'] . '" ' . ($filtro_proveedor == $proveedor['id'] ? "selected" : "") . '>' . htmlspecialchars($proveedor['razon_social']) . '</option>';
                            endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="fecha_desde" class="form-label mb-1">Desde</label>
                        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?= htmlspecialchars($filtro_fecha_desde) ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="fecha_hasta" class="form-label mb-1">Hasta</label>
                        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?= htmlspecialchars($filtro_fecha_hasta) ?>">
                    </div>
                    <div class="col-md-1 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary me-2"><i class="bi bi-search"></i></button>
                        <a href="compras.php" class="btn btn-danger" title="Limpiar filtros"><i class="bi bi-arrow-clockwise"></i></a>
                    </div>
                </div>
                <div class="row mt-3"><div class="col-12 text-center"><a href="compra_form.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Nueva O. de Compra</a></div></div>
            </form>
        </div>
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'numero_orden', 'dir' => ($orden_campo === 'numero_orden' && $orden_direccion === 'ASC') ? 'DESC' : 'ASC'])) ?>">Nro. Orden <i class="bi <?= ($orden_campo === 'numero_orden') ? (($orden_direccion === 'ASC') ? 'bi-arrow-up' : 'bi-arrow-down') : '' ?>"></i></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'proveedor_nombre', 'dir' => ($orden_campo === 'proveedor_nombre' && $orden_direccion === 'ASC') ? 'DESC' : 'ASC'])) ?>">Proveedor <i class="bi <?= ($orden_campo === 'proveedor_nombre') ? (($orden_direccion === 'ASC') ? 'bi-arrow-up' : 'bi-arrow-down') : '' ?>"></i></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'fecha_orden', 'dir' => ($orden_campo === 'fecha_orden' && $orden_direccion === 'ASC') ? 'DESC' : 'ASC'])) ?>">Fecha <i class="bi <?= ($orden_campo === 'fecha_orden') ? (($orden_direccion === 'ASC') ? 'bi-arrow-up' : 'bi-arrow-down') : '' ?>"></i></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'estado_id', 'dir' => ($orden_campo === 'estado_id' && $orden_direccion === 'ASC') ? 'DESC' : 'ASC'])) ?>">Estado <i class="bi <?= ($orden_campo === 'estado_id') ? (($orden_direccion === 'ASC') ? 'bi-arrow-up' : 'bi-arrow-down') : '' ?>"></i></a></th>
                            <th class="text-end">Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ordenes_compra)):
                            echo '<tr><td colspan="6" class="text-center py-4"><i class="bi bi-inbox display-4 text-muted"></i><p class="text-muted mt-2">No se encontraron órdenes de compra.</p></td></tr>';
                        else:
                            foreach ($ordenes_compra as $orden):
                                echo '<tr>';
                                echo '<td><code class="text-primary">' . htmlspecialchars($orden['numero_orden']) . '</code></td>';
                                echo '<td><div class="fw-bold text-truncate">' . htmlspecialchars($orden['proveedor_nombre']) . '</div></td>';
                                echo '<td>' . date('d/m/Y', strtotime($orden['fecha_orden'])) . '</td>';
                                echo '<td>';
                                            $estado_nombre = $orden['nombre_estado'] ?? 'Desconocido';
                                            $clase_badge = 'bg-secondary';
                                            $nombre_normalizado = trim(strtolower($estado_nombre));

                                            if ($nombre_normalizado == 'pendiente') { $clase_badge = 'bg-warning text-dark'; }
                                            elseif ($nombre_normalizado == 'confirmada') { $clase_badge = 'bg-info'; }
                                            elseif ($nombre_normalizado == 'entregada' || $nombre_normalizado == 'recibida') { $clase_badge = 'bg-success'; }
                                            elseif ($nombre_normalizado == 'cancelada') { $clase_badge = 'bg-danger'; }
                                            elseif ($nombre_normalizado == 'parcialmente entregada' || $nombre_normalizado == 'parcial') { $clase_badge = 'bg-primary'; }
                                            
                                            echo "<span class=\"badge {$clase_badge}\">" . htmlspecialchars($estado_nombre) . "</span>";
                                echo '</td>';
                                echo '<td class="text-end"><div class="fw-bold text-success">$' . number_format($orden['total'], 2) . '</div></td>';
                                echo '<td class="col-acciones">';
                                echo '<div class="btn-group" role="group">';
                                echo '<a href="compra_form.php?id=' . $orden['id_orden'] . '" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil"></i></a>';
                                echo '<a href="compra_detalle.php?id=' . $orden['id_orden'] . '" class="btn btn-sm btn-info" title="Ver Detalle"><i class="bi bi-eye"></i></a>';
                                echo '<a href="compra_imprimir.php?id=' . $orden['id_orden'] . '" class="btn btn-sm btn-secondary" title="Imprimir" target="_blank"><i class="bi bi-printer"></i></a>';
                                echo '<a href="gestionar_compra.php?id=' . $orden['id_orden'] . '&accion=eliminar" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm(\'¿Eliminar la orden de compra?\');"><i class="bi bi-trash"></i></a>';
                                echo '</div>';
                                echo '</td>';
                                echo '</tr>';
                            endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_pages > 1):
                echo '<div class="mt-3">';
                echo '<nav><ul class="pagination justify-content-center mb-0">';
                if ($page > 1):
                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '"><i class="bi bi-chevron-double-left"></i></a></li>';
                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $page - 1])) . '"><i class="bi bi-chevron-left"></i></a></li>';
                endif;
                for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++):
                    echo '<li class="page-item ' . ($i === $page ? 'active' : '') . '"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '">' . $i . '</a></li>';
                endfor;
                if ($page < $total_pages):
                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $page + 1])) . '"><i class="bi bi-chevron-right"></i></a></li>';
                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '"><i class="bi bi-chevron-double-right"></i></a></li>';
                endif;
                echo '</ul></nav>';
                echo '<div class="text-center mt-2"><small class="text-muted">Página ' . $page . ' de ' . $total_pages . ' (' . number_format($ordenes_compra_filtradas) . ' resultados)</small></div>';
                echo '</div>';
            endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>