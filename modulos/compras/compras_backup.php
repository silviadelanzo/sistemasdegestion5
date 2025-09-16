<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');
header('Content-Type: text/html; charset=UTF-8');

// --- Lógica de Paginación y Filtros ---
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Filtros
$filtro_busqueda    = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_proveedor   = isset($_GET['proveedor']) ? trim($_GET['proveedor']) : '';
$filtro_fecha_desde = isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

// Ordenamiento
$orden_campo = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_compra';
$orden_direccion = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
$campos_permitidos = ['codigo', 'fecha_compra', 'estado', 'total', 'proveedor_id'];
if (!in_array($orden_campo, $campos_permitidos)) {
    $orden_campo = 'fecha_compra';
}

$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // --- Construcción de la consulta con filtros ---
    $params = [];
    $sql_base = "FROM compras c 
        LEFT JOIN proveedores p ON c.proveedor_id = p.id 
        LEFT JOIN usuarios u ON c.usuario_id = u.id
        LEFT JOIN compra_detalles cd ON c.id = cd.compra_id
        LEFT JOIN productos pr ON cd.producto_id = pr.id";
    
    $where_conditions = [];

    if ($filtro_busqueda !== '') {
        $where_conditions[] = "(c.codigo LIKE :busqueda OR p.razon_social LIKE :busqueda OR pr.nombre LIKE :busqueda)";
        $params[':busqueda'] = "%{$filtro_busqueda}%";
    }
    if ($filtro_proveedor !== '' && $filtro_proveedor !== 'todos') {
        $where_conditions[] = "c.proveedor_id = :proveedor";
        $params[':proveedor'] = $filtro_proveedor;
    }
    if ($filtro_fecha_desde !== '') {
        $where_conditions[] = "c.fecha_compra >= :fecha_desde";
        $params[':fecha_desde'] = $filtro_fecha_desde;
    }
    if ($filtro_fecha_hasta !== '') {
        $where_conditions[] = "c.fecha_compra <= :fecha_hasta";
        $params[':fecha_hasta'] = $filtro_fecha_hasta;
    }

    $where_clause = $where_conditions ? ' WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // --- Consulta para obtener los datos ---
    $sql = "SELECT DISTINCT c.*, p.razon_social as proveedor_nombre, p.nombre_comercial, u.nombre as usuario_nombre "
        . $sql_base 
        . $where_clause 
        . " ORDER BY c.{$orden_campo} {$orden_direccion} "
        . " LIMIT {$per_page} OFFSET {$offset}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $compras = $stmt->fetchAll();

    // --- Conteo total ---
    $count_sql = "SELECT COUNT(DISTINCT c.id) " . $sql_base . $where_clause;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $compras_filtradas = $count_stmt->fetchColumn();
    $total_pages = $compras_filtradas > 0 ? ceil($compras_filtradas / $per_page) : 1;

    // --- Datos para tarjetas y filtros ---
    $total_compras        = $pdo->query("SELECT COUNT(*) FROM compras")->fetchColumn();
    $compras_pendientes   = $pdo->query("SELECT COUNT(*) FROM compras WHERE estado='pendiente'")->fetchColumn();
    $compras_confirmadas  = $pdo->query("SELECT COUNT(*) FROM compras WHERE estado='confirmada'")->fetchColumn();
    $compras_recibidas    = $pdo->query("SELECT COUNT(*) FROM compras WHERE estado='recibida'")->fetchColumn();
    $valor_total          = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM compras")->fetchColumn();
    $proveedores = $pdo->query("SELECT id, razon_social FROM proveedores WHERE activo = 1 ORDER BY razon_social")->fetchAll();

} catch (Exception $e) {
    $error_message = "Error al cargar datos: " . $e->getMessage();
    $compras = []; $total_pages = 1; $total_compras = 0; $valor_total = 0; $proveedores = [];
    $compras_pendientes = 0; $compras_confirmadas = 0; $compras_recibidas = 0;
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

        .main-container {
            margin: 0 auto;
            max-width: 1000px; /* un poco más ancho */
        }

        /* Contenedores más compactos */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-top: 16px;
            padding: 10px 12px;
        }
        .search-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 10px 12px;
            margin-top: 12px;
        }

        /* Tarjetas superiores más chicas */
        .info-cards {
            display: flex;
            gap: 8px;
            margin: 12px 0 6px 0;
        }
        .info-card {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 8px;
            color: #fff;
            font-weight: 500;
            font-size: 0.85rem;
            line-height: 1.1;
            min-height: 36px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.04);
        }
        .info-card .icon {
            font-size: 1.2rem;
            margin-left: auto;
            opacity: 0.65;
        }
        .ic-blue   { background: linear-gradient(90deg, #396afc 0%, #2948ff 100%); }
        .ic-yellow { background: linear-gradient(90deg, #fc4a1a 0%, #f7b733 100%); }
        .ic-green  { background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%); }
        .ic-cyan   { background: linear-gradient(90deg, #a7a7a7 0%, #636363 100%); }
        .ic-purple { background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); }

        /* Tabla compacta, alternada y con separadores entre órdenes */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.92rem;
        }
        .table thead th {
            border-top: none !important;
            border-bottom: 1px solid #e9ecef !important; /* solo línea bajo encabezado */
            padding-top: 6px;
            padding-bottom: 6px;
            white-space: nowrap;
        }
        .table tbody tr {
            border-top: none !important;
            border-bottom: 1px solid #e9ecef; /* divisor entre órdenes */
        }
        .table tbody tr:last-child { border-bottom: none; }

        .table tbody tr:nth-child(odd)  { background-color: #ffffff; }
        .table tbody tr:nth-child(even) { background-color: #f8f9fb; }

        .table tbody td {
            border-top: none !important;
            border-bottom: none !important;
            padding: 6px 8px; /* menos espacio entre campos */
            vertical-align: middle;
        }

        /* Alineación y ancho de Total */
        .table td.text-end,
        .table th.text-end { font-variant-numeric: tabular-nums; }
        .mono-nums { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }

        /* Dar aire a la columna Total */
        th:nth-child(5), td:nth-child(5) { min-width: 120px; }

        /* Columna proveedor con ancho limitado */
        .td-proveedor .fw-bold { max-width: 220px; }

        /* Acciones más compactas y con color */
        td.col-acciones { min-width: 180px; } /* ancho sugerido para no romper */
        .btn-accion {
            color: #0b0b0b;
            border: none;
            padding: 6px 10px;
            font-size: 0.95rem;
        }
        .btn-accion:hover { opacity: 0.9; }
        .btn-ver    { background-color: #11d5f3; } /* celeste */
        .btn-editar { background-color: #ffc107; } /* amarillo */
        .btn-borrar { background-color: #ff6b6b; } /* rojo suave */
        .btn-group .btn + .btn { margin-left: 6px; }
    </style>
</head>

<body>
    <?php include "../../config/navbar_code.php"; ?>
    <div class="main-container">

        <!-- Tarjetas resumen -->
        <div class="info-cards">
            <div class="info-card ic-blue">
                <?= number_format($total_compras) ?>
                <span style="font-size:0.8rem;font-weight:400;margin-left:6px;">Total</span>
                <span class="icon ms-2"><i class="bi bi-cart"></i></span>
            </div>
            <div class="info-card ic-yellow">
                <?= number_format($compras_pendientes) ?>
                <span style="font-size:0.8rem;font-weight:400;margin-left:6px;">Pendientes</span>
                <span class="icon ms-2"><i class="bi bi-clock"></i></span>
            </div>
            <div class="info-card ic-green">
                <?= number_format($compras_confirmadas) ?>
                <span style="font-size:0.8rem;font-weight:400;margin-left:6px;">Confirmadas</span>
                <span class="icon ms-2"><i class="bi bi-check-circle"></i></span>
            </div>
            <div class="info-card ic-cyan">
                <?= number_format($compras_recibidas) ?>
                <span style="font-size:0.8rem;font-weight:400;margin-left:6px;">Recibidas</span>
                <span class="icon ms-2"><i class="bi bi-box-seam"></i></span>
            </div>
            <div class="info-card ic-purple">
                $<?= number_format($valor_total, 2) ?>
                <span style="font-size:0.8rem;font-weight:400;margin-left:6px;">Valor Total</span>
                <span class="icon ms-2"><i class="bi bi-currency-dollar"></i></span>
            </div>
        </div>

        <!-- Buscador y Filtros -->
        <div class="search-section">
            <form method="GET">
                <!-- Fila 1: Buscador a lo ancho -->
                <div class="row g-2 align-items-center">
                    <div class="col-12">
                        <label for="busqueda" class="visually-hidden">Buscar</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="busqueda" name="busqueda" placeholder="Buscar por código, producto, proveedor..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
                            <button type="submit" class="btn btn-primary" title="Buscar"><i class="bi bi-search"></i></button>
                            <a href="compras_backup.php" class="btn btn-outline-secondary" title="Limpiar filtros"><i class="bi bi-arrow-clockwise"></i></a>
                            <button class="btn btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#filtrosAvanzados" aria-expanded="false" aria-controls="filtrosAvanzados" title="Filtros avanzados">
                                <i class="bi bi-funnel"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filtros avanzados (colapsable) -->
                <div class="collapse show mt-2" id="filtrosAvanzados">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label for="proveedor" class="form-label mb-1">Proveedor</label>
                            <select class="form-select" id="proveedor" name="proveedor">
                                <option value="todos">Todos los proveedores</option>
                                <?php foreach ($proveedores as $proveedor): ?>
                                    <option value="<?= $proveedor['id'] ?>" <?= $filtro_proveedor == $proveedor['id'] ? "selected" : "" ?>>
                                        <?= htmlspecialchars($proveedor['razon_social']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_desde" class="form-label mb-1">Fecha desde</label>
                            <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?= htmlspecialchars($filtro_fecha_desde) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_hasta" class="form-label mb-1">Fecha hasta</label>
                            <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?= htmlspecialchars($filtro_fecha_hasta) ?>">
                        </div>
                    </div>
                    <div class="text-end mt-2">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Aplicar filtros</button>
                    </div>
                </div>

                <!-- Fila 2: Botón Nueva Compra centrado -->
                <div class="row mt-3">
                    <div class="col-12 text-center">
                        <a href="compras_form.php" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> Nueva Compra
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th class="text-end">Total</th>
                            <th>Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($compras)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="bi bi-inbox display-4 text-muted"></i>
                                    <p class="text-muted mt-2">No se encontraron compras con los filtros aplicados.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($compras as $compra): ?>
                                <tr>
                                    <td><code class="text-primary"><?= htmlspecialchars($compra['codigo']) ?></code></td>
                                    <td class="td-proveedor">
                                        <!-- Una sola línea por proveedor: sin nombre_comercial -->
                                        <div class="fw-bold text-truncate"><?= htmlspecialchars($compra['proveedor_nombre']) ?></div>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($compra['fecha_compra'])) ?></td>
                                    <td>
                                        <?php
                                            $estado = $compra['estado'];
                                            $clase_badge = 'bg-secondary';
                                            if ($estado == 'pendiente')  $clase_badge = 'bg-warning text-dark';
                                            if ($estado == 'confirmada') $clase_badge = 'bg-info';
                                            if ($estado == 'recibida')   $clase_badge = 'bg-success';
                                            if ($estado == 'cancelada')  $clase_badge = 'bg-danger';
                                            echo "<span class=\"badge {$clase_badge}\">" . ucfirst($estado) . "</span>";
                                        ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold text-success mono-nums">$<?= number_format($compra['total'], 2) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($compra['usuario_nombre'] ?? 'N/A') ?></td>
                                    <td class="col-acciones">
                                        <div class="btn-group" role="group" aria-label="Acciones">
                                            <!-- Orden: Ver, Editar, Borrar con colores -->
                                            <a href="compra_detalle.php?id=<?= $compra['id'] ?>" class="btn btn-sm btn-accion btn-ver" title="Ver">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="compra_form.php?id=<?= $compra['id'] ?>" class="btn btn-sm btn-accion btn-editar" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="gestionar_compra.php?id=<?= $compra['id'] ?>&accion=eliminar" class="btn btn-sm btn-accion btn-borrar" title="Eliminar" onclick="return confirm('¿Eliminar la compra?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="mt-3">
                    <nav aria-label="Navegación de compras">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>"><i class="bi bi-chevron-double-left"></i></a></li>
                                <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"><i class="bi bi-chevron-left"></i></a></li>
                            <?php endif; ?>
                            <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"><i class="bi bi-chevron-right"></i></a></li>
                                <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><i class="bi bi-chevron-double-right"></i></a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <div class="text-center mt-2">
                        <small class="text-muted">Página <?= $page ?> de <?= $total_pages ?> (<?= number_format($compras_filtradas) ?> resultados)</small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>