<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');
header('Content-Type: text/html; charset=UTF-8');

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$filtro_busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$filtro_proveedor = isset($_GET['proveedor']) ? trim($_GET['proveedor']) : '';

$orden_campo = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_entrega';
$orden_direccion = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';

$campos_permitidos = ['codigo', 'numero_remito_proveedor', 'fecha_entrega', 'estado', 'codigo_proveedor', 'fecha_creacion'];
if (!in_array($orden_campo, $campos_permitidos)) {
    $orden_campo = 'fecha_entrega';
}

$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
$usuario_rol = $_SESSION['rol_usuario'] ?? 'inventario';
$es_administrador = ($usuario_rol === 'admin' || $usuario_rol === 'administrador');

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Cards resumen
    $total_remitos = $pdo->query("SELECT COUNT(*) FROM remitos")->fetchColumn();
    $remitos_pendientes = $pdo->query("SELECT COUNT(*) FROM remitos WHERE estado='pendiente'")->fetchColumn();
    $remitos_confirmados = $pdo->query("SELECT COUNT(*) FROM remitos WHERE estado='confirmado'")->fetchColumn();
    $remitos_recibidos = $pdo->query("SELECT COUNT(*) FROM remitos WHERE estado='recibido'")->fetchColumn();
    
    // Obtener total de productos en remitos
    $total_productos = $pdo->query("SELECT COALESCE(SUM(rd.cantidad), 0) FROM remito_detalles rd JOIN remitos r ON rd.remito_id = r.id")->fetchColumn();

    // Filtros para listado
    $where_conditions = [];
    $params = [];

    if ($filtro_busqueda !== '') {
        $where_conditions[] = "(r.codigo LIKE ? OR r.numero_remito_proveedor LIKE ? OR r.codigo_proveedor LIKE ? OR p.razon_social LIKE ? OR p.nombre_comercial LIKE ?)";
        $busqueda = "%{$filtro_busqueda}%";
        array_push($params, $busqueda, $busqueda, $busqueda, $busqueda, $busqueda);
    }
    if ($filtro_estado !== '' && $filtro_estado !== 'todos') {
        $where_conditions[] = "r.estado = ?";
        $params[] = $filtro_estado;
    }
    if ($filtro_proveedor !== '' && $filtro_proveedor !== 'todos') {
        $where_conditions[] = "r.proveedor_id = ?";
        $params[] = $filtro_proveedor;
    }
    $where_clause = $where_conditions ? implode(' AND ', $where_conditions) : '1';

    $orden_sql = 'r.' . $orden_campo;

    // Listado de remitos
    $sql = "SELECT r.*, 
                   COALESCE(p.razon_social, p.nombre_comercial, 'Proveedor') as proveedor_nombre,
                   p.nombre_comercial,
                   COALESCE(u.nombre, u.username, 'Usuario') as usuario_nombre,
                   COUNT(rd.id) as total_productos_remito,
                   SUM(rd.cantidad) as total_cantidad
            FROM remitos r
            LEFT JOIN proveedores p ON r.proveedor_id = p.id
            LEFT JOIN usuarios u ON r.usuario_id = u.id
            LEFT JOIN remito_detalles rd ON r.id = rd.remito_id
            WHERE $where_clause
            GROUP BY r.id
            ORDER BY $orden_sql $orden_direccion
            LIMIT $per_page OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $remitos = $stmt->fetchAll();

    $count_sql = "SELECT COUNT(DISTINCT r.id) FROM remitos r
                  LEFT JOIN proveedores p ON r.proveedor_id = p.id
                  WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $remitos_filtrados = $count_stmt->fetchColumn();
    $total_pages = ceil($remitos_filtrados / $per_page);

    // Obtener proveedores para filtro
    $proveedores = $pdo->query("SELECT id, COALESCE(razon_social, nombre_comercial, 'Proveedor') as nombre FROM proveedores WHERE activo = 1 ORDER BY razon_social, nombre_comercial")->fetchAll();
} catch (Exception $e) {
    $error_message = "Error al cargar remitos: " . $e->getMessage();
    $remitos = [];
    $total_pages = 1;
    $total_remitos = 0;
    $remitos_pendientes = 0;
    $remitos_confirmados = 0;
    $remitos_recibidos = 0;
    $total_productos = 0;
}

$pageTitle = "Gestión de Remitos - " . SISTEMA_NOMBRE;
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
        body {
            background-color: #f8f9fa;
        }

        .main-container {
            margin: 0 auto;
            max-width: 1200px;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-top: 30px;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .btn-action {
            padding: 4px 8px;
            margin: 0 1px;
            border-radius: 5px;
            font-size: 0.85rem;
        }

        .search-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 15px 20px;
            margin-top: 20px;
        }

        .info-cards {
            display: flex;
            gap: 20px;
            margin: 30px 0 10px 0;
        }

        @media (max-width: 991px) {
            .info-cards {
                flex-direction: column;
                gap: 12px;
            }
        }

        .info-card {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 12px;
            color: #fff;
            font-weight: 600;
            font-size: 1.2rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            min-width: 200px;
        }

        .ic-blue {
            background: linear-gradient(90deg, #396afc 0%, #2948ff 100%);
        }

        .ic-yellow {
            background: linear-gradient(90deg, #fc4a1a 0%, #f7b733 100%);
        }

        .ic-green {
            background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
        }

        .ic-cyan {
            background: linear-gradient(90deg, #a7a7a7 0%, #636363 100%);
        }

        .ic-purple {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .info-card .icon {
            font-size: 2.3rem;
            margin-left: auto;
            opacity: 0.7;
        }

        .pagination-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-top: 30px;
            padding: 15px 0;
        }

        .badge-estado {
            padding: 6px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <?php include "../../config/navbar_code.php"; ?>
    <div class="main-container">

        <!-- Tarjetas resumen -->
        <div class="info-cards">
            <div class="info-card ic-blue">
                <?= number_format($total_remitos) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Total Remitos</span>
                <span class="icon ms-2"><i class="bi bi-file-earmark-text"></i></span>
            </div>
            <div class="info-card ic-yellow">
                <?= number_format($remitos_pendientes) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Pendientes</span>
                <span class="icon ms-2"><i class="bi bi-clock"></i></span>
            </div>
            <div class="info-card ic-green">
                <?= number_format($remitos_confirmados) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Confirmados</span>
                <span class="icon ms-2"><i class="bi bi-check-circle"></i></span>
            </div>
            <div class="info-card ic-cyan">
                <?= number_format($remitos_recibidos) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Recibidos</span>
                <span class="icon ms-2"><i class="bi bi-box-seam"></i></span>
            </div>
            <div class="info-card ic-purple">
                <?= number_format($total_productos) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Productos Total</span>
                <span class="icon ms-2"><i class="bi bi-boxes"></i></span>
            </div>
        </div>

        <!-- Buscador/Filtros -->
        <div class="search-section">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Buscar</label>
                    <input type="text" class="form-control" name="busqueda" placeholder="Código, remito, proveedor..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Estado</label>
                    <select class="form-select" name="estado">
                        <option value="todos">Todos</option>
                        <option value="pendiente" <?= $filtro_estado == "pendiente" ? "selected" : "" ?>>Pendiente</option>
                        <option value="confirmado" <?= $filtro_estado == "confirmado" ? "selected" : "" ?>>Confirmado</option>
                        <option value="recibido" <?= $filtro_estado == "recibido" ? "selected" : "" ?>>Recibido</option>
                        <option value="cancelado" <?= $filtro_estado == "cancelado" ? "selected" : "" ?>>Cancelado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Proveedor</label>
                    <select class="form-select" name="proveedor">
                        <option value="todos">Todos</option>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <option value="<?= $proveedor['id'] ?>" <?= $filtro_proveedor == $proveedor['id'] ? "selected" : "" ?>>
                                <?= htmlspecialchars($proveedor['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Nuevo Remito primero, Filtrar abajo -->
                <div class="col-md-2 d-grid gap-2">
                    <a href="remito_form.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Nuevo Remito</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
                </div>
                <div class="col-md-2 d-grid gap-2">
                    <a href="exportar_remitos.php" class="btn btn-info"><i class="bi bi-file-excel"></i> Exportar</a>
                    <a href="reportes_remitos.php" class="btn btn-secondary"><i class="bi bi-graph-up"></i> Reportes</a>
                </div>
            </form>
        </div>

        <div class="table-container p-3">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h4 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lista de Remitos</h4>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>
                                <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'codigo', 'dir' => $orden_campo === 'codigo' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                                    Código <i class="bi bi-arrow-down-up"></i>
                                </a>
                            </th>
                            <th>
                                <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'numero_remito_proveedor', 'dir' => $orden_campo === 'numero_remito_proveedor' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                                    Nro. Remito <i class="bi bi-arrow-down-up"></i>
                                </a>
                            </th>
                            <th>Proveedor</th>
                            <th>
                                <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'fecha_entrega', 'dir' => $orden_campo === 'fecha_entrega' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                                    <b>Fecha Entrega</b> <i class="bi bi-arrow-down-up"></i>
                                </a>
                            </th>
                            <th>
                                <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'estado', 'dir' => $orden_campo === 'estado' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                                    Estado <i class="bi bi-arrow-down-up"></i>
                                </a>
                            </th>
                            <th>Productos</th>
                            <th>Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($remitos)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-inbox display-4 text-muted"></i>
                                    <p class="text-muted mt-2">No se encontraron remitos</p>
                                    <?php if (isset($error_message)): ?>
                                        <p class="text-danger"><?= htmlspecialchars($error_message) ?></p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($remitos as $remito): ?>
                                <tr>
                                    <td><code class="text-primary"><?= htmlspecialchars($remito['codigo']) ?></code></td>
                                    <td>
                                        <?php if (!empty($remito['numero_remito_proveedor'])): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($remito['numero_remito_proveedor']) ?></span>
                                        <?php else: ?>
                                            <small class="text-muted">Sin número</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-building text-muted me-1"></i>
                                            <div>
                                                <div class="fw-bold text-truncate" style="max-width: 150px;"><?= htmlspecialchars($remito['proveedor_nombre']) ?></div>
                                                <?php if (!empty($remito['codigo_proveedor'])): ?>
                                                    <span class="badge bg-primary ms-1"><?= htmlspecialchars($remito['codigo_proveedor']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($remito['observaciones']) && stripos($remito['observaciones'], 'Cargado por OCR') !== false): ?>
                                                    <span class="badge bg-info ms-1">OCR</span>
                                                <?php endif; ?>
                                                <?php if ($remito['nombre_comercial']): ?>
                                                    <small class="text-muted text-truncate d-block" style="max-width: 150px;"><?= htmlspecialchars($remito['nombre_comercial']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><b><?= htmlspecialchars(formatDateES($remito['fecha_entrega'])) ?></b></div>
                                        <small class="text-muted"><?= htmlspecialchars(date('H:i', strtotime($remito['fecha_creacion']))) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $estado = $remito['estado'];
                                        echo $estado == "pendiente" ? '<span class="badge bg-warning text-dark">Pendiente</span>' : 
                                            ($estado == "confirmado" ? '<span class="badge bg-info">Confirmado</span>' : 
                                            ($estado == "recibido" ? '<span class="badge bg-success">Recibido</span>' :
                                            '<span class="badge bg-danger">Cancelado</span>'));
                                        ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-info"><?= number_format($remito['total_productos_remito']) ?> items</div>
                                        <?php if ($remito['total_cantidad'] > 0): ?>
                                            <small class="text-muted">Cant: <?= number_format($remito['total_cantidad']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-person text-muted me-1"></i>
                                        <?= htmlspecialchars($remito['usuario_nombre'] ?? 'N/A') ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="remito_detalle.php?id=<?= $remito['id'] ?>" class="btn btn-info btn-action" title="Ver Detalle"><i class="bi bi-eye"></i></a>
                                            <a href="remito_form.php?id=<?= $remito['id'] ?>" class="btn btn-warning btn-action" title="Editar"><i class="bi bi-pencil"></i></a>
                                            <a href="remito_imprimir.php?id=<?= $remito['id'] ?>" class="btn btn-success btn-action" title="Imprimir PDF" target="_blank"><i class="bi bi-printer"></i></a>
                                            <a href="cambiar_estado_remito.php?id=<?= $remito['id'] ?>&accion=confirmar" class="btn btn-primary btn-action" title="Confirmar" onclick="return confirm('¿Confirmar este remito?')"><i class="bi bi-check"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Navegación de remitos">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">
                                        <i class="bi bi-chevron-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>">
                                        <i class="bi bi-chevron-double-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <div class="text-center mt-2">
                        <small class="text-muted">
                            Mostrando <?= min($per_page * ($page - 1) + 1, $remitos_filtrados) ?> - <?= min($per_page * $page, $remitos_filtrados) ?> de <?= number_format($remitos_filtrados) ?> remitos
                        </small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
