<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');
header('Content-Type: text/html; charset=UTF-8');

// Paginación y filtros
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$filtro_categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
$filtro_lugar = isset($_GET['lugar']) ? intval($_GET['lugar']) : 0;
$filtro_busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

$orden_campo = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_creacion';
$orden_direccion = (isset($_GET['dir']) && strtolower($_GET['dir']) === 'asc') ? 'ASC' : 'DESC';
// Se agrega 'pedidos_pendientes' a los campos permitidos para ordenar
$campos_permitidos = ['codigo', 'nombre', 'categoria_nombre', 'lugar_nombre', 'stock', 'stock_minimo', 'precio_venta', 'fecha_creacion', 'pedidos_pendientes'];
if (!in_array($orden_campo, $campos_permitidos)) $orden_campo = 'fecha_creacion';

$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
$usuario_rol = $_SESSION['rol_usuario'] ?? 'inventario';
$es_administrador = ($usuario_rol === 'admin' || $usuario_rol === 'administrador');

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Badges menú superior (puedes agregar más si necesitas)
    $compras_pendientes = 0;
    $facturas_pendientes = 0;
    $tablas_existentes = [];
    $stmt_tables = $pdo->query("SHOW TABLES");
    if ($stmt_tables) while ($row_table = $stmt_tables->fetch(PDO::FETCH_NUM)) $tablas_existentes[] = $row_table[0];
    if (in_array('compras', $tablas_existentes)) {
        $stmt = $pdo->query("SELECT COUNT(*) as pendientes FROM compras WHERE estado IN ('pendiente', 'confirmada')");
        if ($stmt) $compras_pendientes = $stmt->fetch()['pendientes'] ?? 0;
    }
    if (in_array('facturas', $tablas_existentes)) {
        $stmt = $pdo->query("SELECT COUNT(*) as pendientes FROM facturas WHERE estado = 'pendiente'");
        if ($stmt) $facturas_pendientes = $stmt->fetch()['pendientes'] ?? 0;
    }

    // Filtros para productos activos
    $where_conditions = ["p.activo=1"];
    $params = [];
    if ($filtro_categoria > 0) {
        $where_conditions[] = "p.categoria_id = ?";
        $params[] = $filtro_categoria;
    }
    if ($filtro_lugar > 0) {
        $where_conditions[] = "p.lugar_id = ?";
        $params[] = $filtro_lugar;
    }
    if (!empty($filtro_busqueda)) {
        $where_conditions[] = "(p.codigo LIKE ? OR p.nombre LIKE ?)";
        $busq = "%{$filtro_busqueda}%";
        $params[] = $busq;
        $params[] = $busq;
    }
    $where_clause = implode(' AND ', $where_conditions);

    // Se ajusta la lógica de ordenamiento para incluir el nuevo campo
    $orden_sql = ($orden_campo == 'categoria_nombre') ? 'c.nombre' : (($orden_campo == 'lugar_nombre') ? 'l.nombre' : (($orden_campo == 'pedidos_pendientes') ? 'pedidos_pendientes' : 'p.' . $orden_campo));

    // MODIFICACIÓN: Se agrega la subconsulta para obtener los pedidos pendientes
    $sql = "SELECT p.*, c.nombre as categoria_nombre, l.nombre as lugar_nombre,
            COALESCE((SELECT SUM(pd.cantidad) 
                      FROM pedido_detalles pd 
                      JOIN pedidos pe ON pd.pedido_id = pe.id 
                      WHERE pe.estado = 'pendiente' AND pd.producto_id = p.id), 0) as pedidos_pendientes
            FROM productos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            LEFT JOIN lugares l ON p.lugar_id = l.id 
            WHERE $where_clause 
            ORDER BY $orden_sql $orden_direccion 
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll();

    $count_sql = "SELECT COUNT(*) 
    FROM productos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id 
    LEFT JOIN lugares l ON p.lugar_id = l.id 
    WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_productos = $count_stmt->fetchColumn();
    $total_pages = ceil($total_productos / $per_page);

    // Estadísticas
    $stats_sql = "SELECT 
    COUNT(*) as total_productos,
    COALESCE(SUM(stock), 0) as stock_total,
    COALESCE(SUM(precio_venta * stock), 0) as valor_total,
    COUNT(CASE WHEN stock <= stock_minimo THEN 1 END) as productos_bajo_stock
    FROM productos WHERE activo=1";
    $stats = $pdo->query($stats_sql)->fetch();

    $categorias = $pdo->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll();
    $lugares = $pdo->query("SELECT id, nombre FROM lugares WHERE activo = 1 ORDER BY nombre")->fetchAll();
} catch (Exception $e) {
    $error_message = "Error al cargar productos: " . $e->getMessage();
    $productos = [];
    $stats = ['total_productos' => 0, 'stock_total' => 0, 'valor_total' => 0, 'productos_bajo_stock' => 0];
    $categorias = [];
    $lugares = [];
    $total_pages = 1;
}
$pageTitle = "Gestión de Productos - " . SISTEMA_NOMBRE;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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

        .badge-categoria {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
    </style>
</head>

<body>
    <?php include "../../config/navbar_code.php"; ?>
    <div class="main-container">
        <!-- Tarjetas resumen -->
        <div class="row my-4 g-3">
            <div class="col-sm-6 col-md-3">
                <div class="card bg-primary text-white h-100 shadow">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fs-4 fw-bold"><?= number_format($stats['total_productos']) ?></div>
                            <div>Total Productos</div>
                        </div>
                        <i class="bi bi-box fs-1"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card bg-warning text-dark h-100 shadow">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fs-4 fw-bold"><?= number_format($stats['productos_bajo_stock']) ?></div>
                            <div>Stock Bajo</div>
                        </div>
                        <i class="bi bi-exclamation-triangle fs-1"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card bg-success text-white h-100 shadow">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fs-4 fw-bold">$<?= number_format($stats['valor_total'], 2) ?></div>
                            <div>Valor Total</div>
                        </div>
                        <i class="bi bi-currency-dollar fs-1"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card bg-info text-dark h-100 shadow">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fs-4 fw-bold"><?= number_format($stats['stock_total']) ?></div>
                            <div>Total Unidades</div>
                        </div>
                        <i class="bi bi-archive fs-1"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros y buscador -->
        <div class="bg-white rounded shadow p-3 mb-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="fw-bold mb-1">Buscar</label>
                    <input type="text" class="form-control" name="busqueda" placeholder="Código, nombre..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
                </div>
                <div class="col-md-3">
                    <label class="fw-bold mb-1">Categoría</label>
                    <select class="form-select" name="categoria">
                        <option value="0">Todas</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($filtro_categoria == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="fw-bold mb-1">Ubicación</label>
                    <select class="form-select" name="lugar">
                        <option value="0">Todas</option>
                        <?php foreach ($lugares as $ubi): ?>
                            <option value="<?= $ubi['id'] ?>" <?= ($filtro_lugar == $ubi['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ubi['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- CAMBIO: Se intercambió el orden de los botones -->
                <div class="col-md-2 d-grid gap-2">
                    <a href="producto_form.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Nuevo Producto</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
                </div>
            </form>
        </div>

        <!-- Tabla Listado de Productos -->
        <div class="table-container p-3">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h4 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lista de Productos</h4>
                <div class="d-flex gap-2">
                    <a href="productos_inactivos.php" class="btn btn-danger"><i class="bi bi-archive"></i> Inactivos</a>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-file-earmark-excel"></i> Exportar
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <h6 class="dropdown-header"><i class="bi bi-file-earmark-excel me-1"></i>Reportes Excel</h6>
                            </li>
                            <li><a class="dropdown-item" href="reporte_completo_excel.php">
                                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Inventario-Categorias-Lugares XLS
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <h6 class="dropdown-header"><i class="bi bi-file-earmark-pdf me-1"></i>Reportes PDF</h6>
                            </li>
                            <li><a class="dropdown-item" href="reporte_total_pdf.php">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>Inventario Total PDF
                                </a></li>
                            <li><a class="dropdown-item" href="reporte_categorias_pdf.php">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>Por Categorías PDF
                                </a></li>
                            <li><a class="dropdown-item" href="reporte_lugares_pdf.php">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>Por Lugares PDF
                                </a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'codigo', 'dir' => $orden_campo === 'codigo' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">Código <i class="bi bi-arrow-down-up"></i></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'nombre', 'dir' => $orden_campo === 'nombre' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">Producto <i class="bi bi-arrow-down-up"></i></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'categoria_nombre', 'dir' => $orden_campo === 'categoria_nombre' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">Categoría <i class="bi bi-arrow-down-up"></i></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'lugar_nombre', 'dir' => $orden_campo === 'lugar_nombre' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">Ubicación <i class="bi bi-arrow-down-up"></i></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'stock', 'dir' => $orden_campo === 'stock' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">Stock <i class="bi bi-arrow-down-up"></i></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'pedidos_pendientes', 'dir' => $orden_campo === 'pedidos_pendientes' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">Pdo. <i class="bi bi-arrow-down-up"></i></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'stock_minimo', 'dir' => $orden_campo === 'stock_minimo' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">Stock Mín. <i class="bi bi-arrow-down-up"></i></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'precio_venta', 'dir' => $orden_campo === 'precio_venta' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">Precio <i class="bi bi-arrow-down-up"></i></a></th>
                            <th><a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'fecha_creacion', 'dir' => $orden_campo === 'fecha_creacion' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">Fecha Alta <i class="bi bi-arrow-down-up"></i></a></th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <i class="bi bi-inbox display-4 text-muted"></i>
                                    <p class="text-muted mt-2">No se encontraron productos</p>
                                    <?php if (isset($error_message)): ?>
                                        <p class="text-danger"><?= htmlspecialchars($error_message) ?></p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productos as $prod): ?>
                                <tr id="producto-<?= $prod['id'] ?>">
                                    <!-- CAMBIO: Se agregó style="white-space: nowrap;" para evitar el salto de línea -->
                                    <td style="white-space: nowrap;"><code class="text-primary"><?= htmlspecialchars($prod['codigo']) ?></code></td>
                                    <td><strong><?= htmlspecialchars($prod['nombre']) ?></strong></td>
                                    <td>
                                        <?php if ($prod['categoria_nombre']): ?>
                                            <span class="badge bg-secondary badge-categoria"><?= htmlspecialchars($prod['categoria_nombre']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin categoría</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($prod['lugar_nombre']): ?>
                                            <span class="badge bg-info text-dark badge-categoria"><?= htmlspecialchars($prod['lugar_nombre']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin ubicación</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="<?= $prod['stock'] <= $prod['stock_minimo'] ? 'text-danger fw-bold' : 'fw-bold' ?>">
                                            <?= number_format($prod['stock']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($prod['pedidos_pendientes'] > 0): ?>
                                            <span class="text-danger fw-bold"><?= number_format($prod['pedidos_pendientes']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="text-muted"><?= number_format($prod['stock_minimo']) ?></span></td>
                                    <td><strong>$<?= number_format($prod['precio_venta'], 2) ?></strong></td>
                                    <td><small class="text-muted"><?= date('d/m/Y', strtotime($prod['fecha_creacion'])) ?></small></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="proform.php?id=<?= $prod['id'] ?>" class="btn btn-warning btn-action" title="Editar"><i class="bi bi-pencil"></i></a>
                                            <a href="editform.php?id=<?= $prod['id'] ?>" class="btn btn-secondary btn-action" title="Editar Imagen"><i class="bi bi-image"></i></a>
                                            <a href="producto_detalle.php?id=<?= $prod['id'] ?>" class="btn btn-info btn-action" title="Ver Detalle"><i class="bi bi-eye"></i></a>
                                            <button onclick="inactivarProducto(<?= $prod['id'] ?>, '<?= htmlspecialchars(addslashes($prod['nombre'])) ?>')" class="btn btn-secondary btn-action" title="Inactivar"><i class="bi bi-archive"></i></button>
                                            <button onclick="eliminarProducto(<?= $prod['id'] ?>, '<?= htmlspecialchars(addslashes($prod['nombre'])) ?>')" class="btn btn-danger btn-action" title="Eliminar"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
                <div class="my-3">
                    <nav aria-label="Navegación de productos">
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
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
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
                                (<?= number_format($total_productos) ?> productos encontrados)
                            </small>
                        </div>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para Inactivar -->
    <div class="modal fade" id="modalInactivar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Inactivación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea inactivar el producto <strong id="nombreProductoInactivar"></strong>?</p>
                    <p class="text-muted">El producto se moverá a la lista de inactivos y no aparecerá en el inventario principal.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" id="confirmarInactivar">
                        <i class="bi bi-archive me-1"></i>Inactivar Producto
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Eliminar -->
    <div class="modal fade" id="modalEliminar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>¡Atención!</strong> Esta acción no se puede deshacer.
                    </div>
                    <p>¿Está seguro que desea eliminar definitivamente el producto <strong id="nombreProductoEliminar"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmarEliminar">
                        <i class="bi bi-trash me-1"></i>Eliminar Definitivamente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let productoIdActual = null;

        function inactivarProducto(id, nombre) {
            productoIdActual = id;
            document.getElementById('nombreProductoInactivar').textContent = nombre;
            new bootstrap.Modal(document.getElementById('modalInactivar')).show();
        }

        function eliminarProducto(id, nombre) {
            productoIdActual = id;
            document.getElementById('nombreProductoEliminar').textContent = nombre;
            new bootstrap.Modal(document.getElementById('modalEliminar')).show();
        }

        document.getElementById('confirmarInactivar').addEventListener('click', function() {
            if (productoIdActual) {
                gestionarProducto('inactivar', productoIdActual);
            }
        });

        document.getElementById('confirmarEliminar').addEventListener('click', function() {
            if (productoIdActual) {
                gestionarProducto('eliminar', productoIdActual);
            }
        });

        function gestionarProducto(accion, id) {
            const btn = accion === 'inactivar' ? document.getElementById('confirmarInactivar') : document.getElementById('confirmarEliminar');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Procesando...';
            btn.disabled = true;

            fetch('gestionar_producto.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        accion: accion,
                        id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modalId = accion === 'inactivar' ? 'modalInactivar' : 'modalEliminar';
                        bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
                        mostrarMensaje(data.message, 'success');
                        setTimeout(() => window.location.reload(), 700);
                    } else {
                        mostrarMensaje(data.message || 'Error desconocido', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarMensaje('Error al procesar la solicitud.', 'danger');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        }

        function mostrarMensaje(mensaje, tipo) {
            const alertContainer = document.createElement('div');
            alertContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1056; min-width: 300px;';
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
            alertDiv.innerHTML = `<i class="bi bi-${tipo === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'} me-2"></i>${mensaje}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            alertContainer.appendChild(alertDiv);
            document.body.appendChild(alertContainer);
            new bootstrap.Alert(alertDiv);
            setTimeout(() => {
                alertDiv.classList.remove('show');
                setTimeout(() => alertContainer.remove(), 150);
            }, 5000);
        }
    </script>
</body>

</html>