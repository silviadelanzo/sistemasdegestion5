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
$filtro_fecha = isset($_GET['fecha']) ? trim($_GET['fecha']) : '';

$orden_campo = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_pedido';
$orden_direccion = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';

$campos_permitidos = ['codigo', 'cliente_nombre', 'fecha_pedido', 'estado', 'total'];
if (!in_array($orden_campo, $campos_permitidos)) {
    $orden_campo = 'fecha_pedido';
}

$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
$usuario_rol = $_SESSION['rol_usuario'] ?? 'inventario';
$es_administrador = ($usuario_rol === 'admin' || $usuario_rol === 'administrador');

$compras_pendientes = 0;
$facturas_pendientes = 0;

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Badges para el menú (compras/facturas pendientes)
    $tablas_existentes = [];
    $stmt_tables = $pdo->query("SHOW TABLES");
    if ($stmt_tables) {
        while ($row_table = $stmt_tables->fetch(PDO::FETCH_NUM)) {
            $tablas_existentes[] = $row_table[0];
        }
    }
    if (in_array('compras', $tablas_existentes)) {
        $stmt = $pdo->query("SELECT COUNT(*) as pendientes FROM compras WHERE estado IN ('pendiente', 'confirmada')");
        if ($stmt) $compras_pendientes = $stmt->fetch()['pendientes'] ?? 0;
    }
    if (in_array('facturas', $tablas_existentes)) {
        $stmt = $pdo->query("SELECT COUNT(*) as pendientes FROM facturas WHERE estado = 'pendiente'");
        if ($stmt) $facturas_pendientes = $stmt->fetch()['pendientes'] ?? 0;
    }

    // Dashboard
    $total_pedidos = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
    $total_clientes = $pdo->query("SELECT COUNT(DISTINCT cliente_id) FROM pedidos")->fetchColumn();
    $total_articulos = $pdo->query("SELECT COALESCE(SUM(pd.cantidad),0) FROM pedido_detalles pd")->fetchColumn();
    $valor_total = $pdo->query("SELECT COALESCE(SUM(total),0) FROM pedidos")->fetchColumn();

    // Filtros para listado
    $where_conditions = [];
    $params = [];

    if ($filtro_busqueda !== '') {
        $where_conditions[] = "(p.codigo LIKE ? OR c.nombre LIKE ? OR c.apellido LIKE ? OR CONCAT(c.nombre,' ',c.apellido) LIKE ?)";
        $busqueda = "%{$filtro_busqueda}%";
        array_push($params, $busqueda, $busqueda, $busqueda, $busqueda);
    }
    if ($filtro_estado !== '') {
        $where_conditions[] = "p.estado = ?";
        $params[] = $filtro_estado;
    }
    if ($filtro_fecha !== '') {
        $where_conditions[] = "DATE(p.fecha_pedido) = ?";
        $params[] = $filtro_fecha;
    }
    $where_clause = $where_conditions ? implode(' AND ', $where_conditions) : '1';

    $orden_sql = $orden_campo === 'cliente_nombre' ? 'c.nombre' : 'p.' . $orden_campo;

    // Listado de pedidos
    $sql = "SELECT p.*, CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre
            FROM pedidos p
            LEFT JOIN clientes c ON p.cliente_id = c.id
            WHERE $where_clause
            ORDER BY $orden_sql $orden_direccion
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll();

    $count_sql = "SELECT COUNT(*)
                  FROM pedidos p
                  LEFT JOIN clientes c ON p.cliente_id = c.id
                  WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $pedidos_filtrados = $count_stmt->fetchColumn();
    $total_pages = ceil($pedidos_filtrados / $per_page);

    // Estados posibles (según ENUM de la tabla pedidos)
    $estados = ['pendiente', 'procesando', 'enviado', 'entregado', 'cancelado'];
} catch (Exception $e) {
    $error_message = "Error al cargar pedidos: " . $e->getMessage();
    $pedidos = [];
    $total_pages = 1;
    $estados = ['pendiente', 'procesando', 'enviado', 'entregado', 'cancelado'];
    $total_pedidos = 0;
    $total_clientes = 0;
    $total_articulos = 0;
    $valor_total = 0;
}

$pageTitle = "Gestión de Pedidos - " . SISTEMA_NOMBRE;
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

        .ic-purple {
            background: linear-gradient(90deg, #6a11cb 0%, #2575fc 100%);
        }

        .ic-pink {
            background: linear-gradient(90deg, #ff758c 0%, #ff7eb3 100%);
        }

        .ic-cyan {
            background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
        }

        .ic-green {
            background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
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

        .navbar-custom {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar-custom .navbar-brand {
            font-weight: bold;
            color: white !important;
            font-size: 1.1rem;
        }

        .navbar-custom .navbar-nav .nav-link {
            color: white !important;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 2px;
            border-radius: 5px;
            padding: 8px 12px !important;
            font-size: 0.95rem;
        }

        .navbar-custom .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        .navbar-custom .navbar-nav .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }

        .navbar-custom .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .navbar-custom .dropdown-item {
            padding: 8px 16px;
            transition: all 0.2s ease;
        }

        .navbar-custom .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
    </style>
</head>

<body>
    <!-- NAVBAR UNIFICADO -->
    <?php include "../../config/navbar_code.php"; ?>

    <div class="main-container">

        <!-- Tarjetas resumen -->
        <div class="info-cards">
            <div class="info-card ic-purple">
                <?= number_format($total_pedidos) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Total Pedidos</span>
                <span class="icon ms-2"><i class="bi bi-list-ul"></i></span>
            </div>
            <div class="info-card ic-pink">
                <?= number_format($total_clientes) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Clientes c/ pedido</span>
                <span class="icon ms-2"><i class="bi bi-person-lines-fill"></i></span>
            </div>
            <div class="info-card ic-cyan">
                <?= number_format($total_articulos) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Artículos pedidos</span>
                <span class="icon ms-2"><i class="bi bi-archive"></i></span>
            </div>
            <div class="info-card ic-green">
                $<?= number_format($valor_total, 2) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Valor total</span>
                <span class="icon ms-2"><i class="bi bi-cash-stack"></i></span>
            </div>
        </div>

        <!-- Buscador/Filtros -->
        <div class="search-section">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Buscar</label>
                    <input type="text" class="form-control" name="busqueda" placeholder="Código, cliente..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Estado</label>
                    <select class="form-select" name="estado">
                        <option value="">Todos</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?= htmlspecialchars($estado) ?>" <?= $filtro_estado === $estado ? 'selected' : '' ?>>
                                <?= ucfirst($estado) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Fecha Pedido</label>
                    <input type="date" class="form-control" name="fecha" value="<?= htmlspecialchars($filtro_fecha) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filtrar</button>
                </div>
                <div class="col-md-2 text-end">
                    <a href="pedido_form.php" class="btn btn-success w-100"><i class="bi bi-plus-circle me-1"></i>Nuevo Pedido</a>
                </div>
            </form>
        </div>

        <!-- Tabla Listado de Pedidos -->
        <div class="table-container p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lista de Pedidos</h4>
                <a href="../../menu_principal.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
            </div>
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>
                            <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'codigo', 'dir' => $orden_campo === 'codigo' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                                Código <i class="bi bi-arrow-down-up"></i>
                            </a>
                        </th>
                        <th>
                            <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'cliente_nombre', 'dir' => $orden_campo === 'cliente_nombre' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                                Cliente <i class="bi bi-arrow-down-up"></i>
                            </a>
                        </th>
                        <th>
                            <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'fecha_pedido', 'dir' => $orden_campo === 'fecha_pedido' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                                Fecha <i class="bi bi-arrow-down-up"></i>
                            </a>
                        </th>
                        <th>
                            <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'estado', 'dir' => $orden_campo === 'estado' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                                Estado <i class="bi bi-arrow-down-up"></i>
                            </a>
                        </th>
                        <th>
                            <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'total', 'dir' => $orden_campo === 'total' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                                Total <i class="bi bi-arrow-down-up"></i>
                            </a>
                        </th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pedidos)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="bi bi-inbox display-4 text-muted"></i>
                                <p class="text-muted mt-2">No se encontraron pedidos</p>
                                <?php if (isset($error_message)): ?>
                                    <p class="text-danger"><?= htmlspecialchars($error_message) ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><code class="text-primary"><?= htmlspecialchars($pedido['codigo']) ?></code></td>
                                <td><strong><?= htmlspecialchars($pedido['cliente_nombre']) ?></strong></td>
                                <td><small class="text-muted"><?= date('d/m/Y', strtotime($pedido['fecha_pedido'])) ?></small></td>
                                <td>
                                    <?php
                                    $estado = htmlspecialchars($pedido['estado']);
                                    $badge = "secondary";
                                    if ($estado === "pendiente") $badge = "warning";
                                    elseif ($estado === "procesando") $badge = "info";
                                    elseif ($estado === "enviado") $badge = "primary";
                                    elseif ($estado === "entregado") $badge = "success";
                                    elseif ($estado === "cancelado") $badge = "danger";
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= ucfirst($estado) ?></span>
                                </td>
                                <td><strong>$<?= number_format($pedido['total'], 2) ?></strong></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="pedido_detalle.php?id=<?= $pedido['id'] ?>" class="btn btn-info btn-action" title="Ver detalles">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="pedido_form.php?id=<?= $pedido['id'] ?>" class="btn btn-warning btn-action" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="pedido_estado.php?id=<?= $pedido['id'] ?>" class="btn btn-secondary btn-action" title="Cambiar Estado">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </a>
                                        <a href="pedido_imprimir.php?id=<?= $pedido['id'] ?>" class="btn btn-success btn-action" title="Imprimir">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Navegación de pedidos">
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
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
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
                        <div class="text-center mt-2">
                            <small class="text-muted">
                                Página <?= $page ?> de <?= $total_pages ?>
                                (<?= number_format($pedidos_filtrados) ?> pedidos encontrados)
                            </small>
                        </div>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>