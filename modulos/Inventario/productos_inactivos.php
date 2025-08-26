<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// --- Datos de usuario y roles ---
$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
$usuario_rol = $_SESSION['rol_usuario'] ?? 'inventario';
$es_administrador = ($usuario_rol === 'admin' || $usuario_rol === 'administrador');

// --- Contadores generales para Navbar ---
$total_clientes = 0; $clientes_nuevos = 0; $pedidos_pendientes = 0;
$facturas_pendientes = 0; $compras_pendientes = 0; $tablas_existentes = [];

// Paginación y filtros
$registros_por_pagina = 20;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;
$filtro_busqueda = trim($_GET['busqueda'] ?? '');
$filtro_categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
$filtro_lugar = isset($_GET['lugar']) ? intval($_GET['lugar']) : 0;
$orden_campo = $_GET['orden'] ?? 'fecha_modificacion';
$orden_direccion = strtoupper($_GET['dir'] ?? 'DESC');
$campos_validos = ['codigo', 'nombre', 'categoria_nombre', 'lugar_nombre', 'stock', 'stock_minimo', 'precio_venta', 'fecha_creacion', 'fecha_modificacion'];
if (!in_array($orden_campo, $campos_validos)) $orden_campo = 'fecha_modificacion';
if (!in_array($orden_direccion, ['ASC', 'DESC'])) $orden_direccion = 'DESC';

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // --- Lógica para los contadores del menú (como en otros listados) ---
    $stmt_tables = $pdo->query("SHOW TABLES"); 
    if ($stmt_tables) { while ($row_table = $stmt_tables->fetch(PDO::FETCH_NUM)) { $tablas_existentes[] = $row_table[0]; } }
    
    if (in_array('clientes', $tablas_existentes)) {
        $stmt_cli_total = $pdo->query("SELECT COUNT(*) as total FROM clientes WHERE activo = 1");
        if($stmt_cli_total) $total_clientes = $stmt_cli_total->fetch()['total'] ?? 0;
    }
    if (in_array('pedidos', $tablas_existentes)) {
        $stmt_ped_pend = $pdo->query("SELECT COUNT(*) as pendientes FROM pedidos WHERE estado = 'pendiente'");
        if($stmt_ped_pend) $pedidos_pendientes = $stmt_ped_pend->fetch()['pendientes'] ?? 0;
    }
    if (in_array('facturas', $tablas_existentes)) {
        $stmt_fact_pend = $pdo->query("SELECT COUNT(*) as pendientes FROM facturas WHERE estado = 'pendiente'");
        if($stmt_fact_pend) $facturas_pendientes = $stmt_fact_pend->fetch()['pendientes'] ?? 0;
    }
    if (in_array('compras', $tablas_existentes)) {
        $stmt_compras_pend = $pdo->query("SELECT COUNT(*) as pendientes FROM compras WHERE estado IN ('pendiente', 'confirmada')");
        if($stmt_compras_pend) $compras_pendientes = $stmt_compras_pend->fetch()['pendientes'] ?? 0;
    }

    // --- Filtros para productos inactivos ---
    $where_conditions = ['p.activo = 0']; $params = [];
    if (!empty($filtro_busqueda)) {
        $where_conditions[] = "(p.codigo LIKE ? OR p.nombre LIKE ? OR p.descripcion LIKE ?)";
        $busqueda_param = "%$filtro_busqueda%";
        $params[] = $busqueda_param; $params[] = $busqueda_param; $params[] = $busqueda_param;
    }
    if ($filtro_categoria > 0) { $where_conditions[] = "p.categoria_id = ?"; $params[] = $filtro_categoria; }
    if ($filtro_lugar > 0) { $where_conditions[] = "p.lugar_id = ?"; $params[] = $filtro_lugar; }
    $where_clause = implode(' AND ', $where_conditions);

    $sql_count = "SELECT COUNT(*) FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id LEFT JOIN lugares l ON p.lugar_id = l.id WHERE $where_clause";
    $stmt_count = $pdo->prepare($sql_count); $stmt_count->execute($params);
    $total_registros = $stmt_count->fetchColumn();
    $total_paginas = ceil($total_registros / $registros_por_pagina);

    $sql_productos = "SELECT p.*, c.nombre as categoria_nombre, l.nombre as lugar_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id LEFT JOIN lugares l ON p.lugar_id = l.id WHERE $where_clause ORDER BY $orden_campo $orden_direccion LIMIT $registros_por_pagina OFFSET $offset";
    $stmt_productos = $pdo->prepare($sql_productos); $stmt_productos->execute($params);
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
    
    $categorias = $pdo->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $lugares = $pdo->query("SELECT id, nombre FROM lugares WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_mensaje = "Error al cargar productos inactivos: " . $e->getMessage();
    $productos = []; $categorias = []; $lugares = []; $total_registros = 0; $total_paginas = 0;
}

$pageTitle = "Productos Inactivos - " . SISTEMA_NOMBRE;

function generarEnlaceOrden($campo, $orden_actual, $direccion_actual) {
    $nueva_direccion = ($campo === $orden_actual && $direccion_actual === 'ASC') ? 'DESC' : 'ASC';
    $params = $_GET;
    $params['orden'] = $campo;
    $params['dir'] = $nueva_direccion;
    return '?' . http_build_query($params);
}

function obtenerIconoOrden($campo, $orden_actual, $direccion_actual) {
    if ($campo !== $orden_actual) return '<i class="bi bi-arrow-down-up text-muted"></i>';
    return $direccion_actual === 'ASC' ? '<i class="bi bi-arrow-up text-primary"></i>' : '<i class="bi bi-arrow-down text-primary"></i>';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos Inactivos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .fondo-caja { background: #fff; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.05), 0 1.5px 4px rgba(0,0,0,0.02); }
        .alert-inactivos { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none; }
        .search-label { font-weight: 500; font-size: 0.92rem; }
        .table-responsive { margin-bottom: 0; }
        .table th, .table td { vertical-align: middle; white-space: nowrap; }
        .badge { font-size: 0.92em; }
        .shadow-bloque { box-shadow: 0 1.5px 12px #0001; }
        /* Rol destacado en navbar */
        .user-rol-badge { font-size: 0.85em; font-weight: 500; background: #e9ecef; color: #333; border-radius: 7px; padding: 3px 10px; margin-left: 6px; }
        @media (max-width: 991.98px) {
            .fondo-caja { border-radius: 10px; }
        }
    </style>
</head>
<body>
<?php include "../../config/navbar_code.php"; ?>

<div class="container-xl my-4">
    <div class="fondo-caja p-4 mb-4 shadow-bloque">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <h2 class="mb-0 fw-bold"><i class="bi bi-archive me-2"></i>Productos Inactivos</h2>
            <div class="d-flex align-items-center gap-2">
                <span class="user-rol-badge">
                    <i class="bi bi-person-circle"></i>
                    <?= htmlspecialchars(ucfirst($usuario_rol)); ?>
                </span>
                <a href="productos.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-2"></i>Volver a Productos Activos</a>
            </div>
        </div>
        <div class="alert alert-inactivos mb-4 py-2 px-3">
            <i class="bi bi-info-circle me-2"></i>
            <b>Productos Inactivos:</b> Estos productos han sido desactivados. Puedes reactivarlos o eliminarlos.
        </div>
        <?php if (isset($error_mensaje)): ?>
        <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error_mensaje); ?></div>
        <?php endif; ?>

        <form method="GET" class="row g-3 align-items-end mb-0">
            <div class="col-md-4">
                <label class="search-label mb-1">Buscar</label>
                <input type="text" class="form-control" name="busqueda" placeholder="Código, nombre..." value="<?= htmlspecialchars($filtro_busqueda); ?>">
            </div>
            <div class="col-md-3">
                <label class="search-label mb-1">Categoría</label>
                <select class="form-select" name="categoria">
                    <option value="0">Todas</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= $categoria['id']; ?>" <?= $filtro_categoria == $categoria['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($categoria['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="search-label mb-1">Ubicación</label>
                <select class="form-select" name="lugar">
                    <option value="0">Todas</option>
                    <?php foreach ($lugares as $lugar): ?>
                        <option value="<?= $lugar['id']; ?>" <?= $filtro_lugar == $lugar['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($lugar['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex flex-row gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filtrar</button>
                <a href="productos_inactivos.php" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-clockwise me-1"></i>Limpiar</a>
            </div>
        </form>
    </div>

    <div class="fondo-caja py-3 px-0 shadow-bloque">
        <div class="d-flex justify-content-between align-items-center px-4 pb-2 border-bottom">
            <h5 class="mb-0 fw-semibold"><i class="bi bi-list me-2"></i>Lista de Productos Inactivos</h5>
            <span class="badge bg-danger fs-6"><?= number_format($total_registros); ?> productos</span>
        </div>
        <div class="table-responsive px-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th onclick="window.location.href='<?= generarEnlaceOrden('codigo', $orden_campo, $orden_direccion); ?>'">Código <?= obtenerIconoOrden('codigo', $orden_campo, $orden_direccion); ?></th>
                        <th onclick="window.location.href='<?= generarEnlaceOrden('nombre', $orden_campo, $orden_direccion); ?>'">Producto <?= obtenerIconoOrden('nombre', $orden_campo, $orden_direccion); ?></th>
                        <th onclick="window.location.href='<?= generarEnlaceOrden('categoria_nombre', $orden_campo, $orden_direccion); ?>'">Categoría <?= obtenerIconoOrden('categoria_nombre', $orden_campo, $orden_direccion); ?></th>
                        <th onclick="window.location.href='<?= generarEnlaceOrden('lugar_nombre', $orden_campo, $orden_direccion); ?>'">Ubicación <?= obtenerIconoOrden('lugar_nombre', $orden_campo, $orden_direccion); ?></th>
                        <th onclick="window.location.href='<?= generarEnlaceOrden('stock', $orden_campo, $orden_direccion); ?>'">Stock <?= obtenerIconoOrden('stock', $orden_campo, $orden_direccion); ?></th>
                        <th onclick="window.location.href='<?= generarEnlaceOrden('precio_venta', $orden_campo, $orden_direccion); ?>'">Precio <?= obtenerIconoOrden('precio_venta', $orden_campo, $orden_direccion); ?></th>
                        <th onclick="window.location.href='<?= generarEnlaceOrden('fecha_modificacion', $orden_campo, $orden_direccion); ?>'">Fecha Inactivación <?= obtenerIconoOrden('fecha_modificacion', $orden_campo, $orden_direccion); ?></th>
                        <th width="130">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2 mb-0">No hay productos inactivos</p>
                                <?php if (!empty($filtro_busqueda) || $filtro_categoria > 0 || $filtro_lugar > 0): ?>
                                    <small class="text-muted">Intenta ajustar los filtros</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productos as $producto): ?>
                            <tr id="producto-<?= $producto['id']; ?>">
                                <td class="text-danger fw-bold"><code><?= htmlspecialchars($producto['codigo']); ?></code></td>
                                <td class="fw-semibold"><?= htmlspecialchars($producto['nombre']); ?>
                                    <?php if (!empty($producto['descripcion'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars(mb_substr($producto['descripcion'], 0, 50)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($producto['categoria_nombre']): ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($producto['categoria_nombre']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin categoría</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($producto['lugar_nombre']): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($producto['lugar_nombre']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin ubicación</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $producto['stock'] <= $producto['stock_minimo'] ? 'danger' : 'success'; ?>">
                                        <?= number_format($producto['stock']); ?>
                                    </span>
                                </td>
                                <td class="fw-bold">$<?= number_format($producto['precio_venta'], 2); ?></td>
                                <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($producto['fecha_modificacion'])); ?></small></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-success btn-sm btn-action" onclick="reactivarProducto(<?= $producto['id']; ?>)" title="Reactivar"><i class="bi bi-arrow-clockwise"></i></button>
                                        <button type="button" class="btn btn-danger btn-sm btn-action" onclick="eliminarProducto(<?= $producto['id']; ?>, '<?= htmlspecialchars(addslashes($producto['nombre'])); ?>')" title="Eliminar"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_paginas > 1): ?>
            <div class="pagination-container mt-3">
                <nav aria-label="Paginación">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($pagina_actual > 1): ?>
                            <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])); ?>"><i class="bi bi-chevron-left"></i></a></li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $pagina_actual - 2); $i <= min($total_paginas, $pagina_actual + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagina_actual ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])); ?>"><?= $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])); ?>"><i class="bi bi-chevron-right"></i></a></li>
                        <?php endif; ?>
                    </ul>
                    <div class="text-center mt-2"><small class="text-muted">Mostrando <?= number_format(($pagina_actual - 1) * $registros_por_pagina + 1); ?> - <?= number_format(min($pagina_actual * $registros_por_pagina, $total_registros)); ?> de <?= number_format($total_registros); ?> productos</small></div>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modales para acciones -->
<div class="modal fade" id="modalReactivar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-arrow-clockwise me-2"></i>Reactivar Producto</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>¿Estás seguro de que deseas reactivar este producto?</p>
        <p class="text-muted">El producto volverá al inventario principal.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="confirmarReactivar"><i class="bi bi-arrow-clockwise me-2"></i>Reactivar</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modalEliminar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Eliminar Producto</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><strong>¡Atención!</strong> Esta acción no se puede deshacer.</div>
        <p>¿Seguro que deseas eliminar <strong id="nombreProductoEliminar"></strong>?</p>
        <p class="text-muted">Los datos se perderán permanentemente.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="confirmarEliminar"><i class="bi bi-trash me-2"></i>Eliminar</button>
      </div>
    </div>
  </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let productoIdSeleccionado = null;

// Mostrar modal para reactivar
function reactivarProducto(id) {
    productoIdSeleccionado = id;
    new bootstrap.Modal(document.getElementById('modalReactivar')).show();
}

// Mostrar modal para eliminar
function eliminarProducto(id, nombre) {
    productoIdSeleccionado = id;
    document.getElementById('nombreProductoEliminar').textContent = nombre;
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}

// Confirmar reactivar
document.getElementById('confirmarReactivar').addEventListener('click', () => {
    if (productoIdSeleccionado) gestionarProducto('reactivar', productoIdSeleccionado);
});
// Confirmar eliminar
document.getElementById('confirmarEliminar').addEventListener('click', () => {
    if (productoIdSeleccionado) gestionarProducto('eliminar', productoIdSeleccionado);
});

function gestionarProducto(accion, id) {
    const btn = accion === 'reactivar'
        ? document.getElementById('confirmarReactivar')
        : document.getElementById('confirmarEliminar');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Procesando...'; btn.disabled = true;

    fetch('gestionar_producto.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({accion: accion, id: id})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Ocultar modal
            const modalEl = document.getElementById(accion === 'reactivar' ? 'modalReactivar' : 'modalEliminar');
            bootstrap.Modal.getInstance(modalEl)?.hide();
            // Eliminar fila de la tabla
            const row = document.getElementById('producto-' + id);
            if (row) row.remove();
            mostrarMensaje(data.message, 'success');
        } else {
            mostrarMensaje(data.message || 'Error desconocido', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('Error de conexión con el servidor.', 'danger');
    })
    .finally(() => {
        btn.innerHTML = originalText; btn.disabled = false;
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
    setTimeout(() => { alertDiv.classList.remove('show'); setTimeout(() => alertContainer.remove(), 150); }, 5000);
}
</script>
</body>
</html>