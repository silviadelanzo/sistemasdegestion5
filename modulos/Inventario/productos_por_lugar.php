<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// --- Lógica de la página y del Navbar unificada ---
$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
$usuario_rol = $_SESSION['rol_usuario'] ?? 'inventario';
$es_administrador = ($usuario_rol === 'admin' || $usuario_rol === 'administrador');

$total_clientes = 0;
$clientes_nuevos = 0;
$pedidos_pendientes = 0;
$facturas_pendientes = 0;
$compras_pendientes = 0;
$tablas_existentes = [];

$lugares_productos = [];
$productos_sin_ubicacion = [];
$totales_generales = ['total_lugares' => 0, 'total_productos' => 0, 'total_stock' => 0, 'valor_total' => 0];
$totales_lugar = [];

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // --- Lógica para el menú (copiada de productos.php) ---
    $stmt_tables = $pdo->query("SHOW TABLES");
    if ($stmt_tables) {
        while ($row_table = $stmt_tables->fetch(PDO::FETCH_NUM)) {
            $tablas_existentes[] = $row_table[0];
        }
    }

    if (in_array('clientes', $tablas_existentes)) {
        $stmt_cli_total = $pdo->query("SELECT COUNT(*) as total FROM clientes WHERE activo = 1");
        if ($stmt_cli_total) $total_clientes = $stmt_cli_total->fetch()['total'] ?? 0;
    }
    if (in_array('pedidos', $tablas_existentes)) {
        $stmt_ped_pend = $pdo->query("SELECT COUNT(*) as pendientes FROM pedidos WHERE estado = 'pendiente'");
        if ($stmt_ped_pend) $pedidos_pendientes = $stmt_ped_pend->fetch()['pendientes'] ?? 0;
    }
    if (in_array('facturas', $tablas_existentes)) {
        $stmt_fact_pend = $pdo->query("SELECT COUNT(*) as pendientes FROM facturas WHERE estado = 'pendiente'");
        if ($stmt_fact_pend) $facturas_pendientes = $stmt_fact_pend->fetch()['pendientes'] ?? 0;
    }
    if (in_array('compras', $tablas_existentes)) {
        $stmt_compras_pend = $pdo->query("SELECT COUNT(*) as pendientes FROM compras WHERE estado IN ('pendiente', 'confirmada')");
        if ($stmt_compras_pend) $compras_pendientes = $stmt_compras_pend->fetch()['pendientes'] ?? 0;
    }
    // --- Fin Lógica para el menú ---

    // --- Lógica de la página ---
    $sql_totales = "SELECT COUNT(DISTINCT l.id) as total_lugares, COUNT(p.id) as total_productos, COALESCE(SUM(p.stock), 0) as total_stock, COALESCE(SUM(p.stock * p.precio_venta), 0) as valor_total FROM lugares l LEFT JOIN productos p ON l.id = p.lugar_id AND p.activo = 1";
    $totales_generales = $pdo->query($sql_totales)->fetch(PDO::FETCH_ASSOC);
    $sql_sin_ubicacion = "SELECT COUNT(*) as productos_sin_ubicacion, COALESCE(SUM(stock), 0) as stock_sin_ubicacion, COALESCE(SUM(stock * precio_venta), 0) as valor_sin_ubicacion FROM productos WHERE lugar_id IS NULL AND activo = 1";
    $sin_ubicacion_totales = $pdo->query($sql_sin_ubicacion)->fetch(PDO::FETCH_ASSOC);
    $totales_generales['total_productos'] += $sin_ubicacion_totales['productos_sin_ubicacion'];
    $totales_generales['total_stock'] += $sin_ubicacion_totales['stock_sin_ubicacion'];
    $totales_generales['valor_total'] += $sin_ubicacion_totales['valor_sin_ubicacion'];
    if ($sin_ubicacion_totales['productos_sin_ubicacion'] > 0) $totales_generales['total_lugares']++;

    $sql_productos_lugar = "SELECT l.id as lugar_id, l.nombre as lugar, p.id as producto_id, p.nombre as producto, p.codigo, p.stock, p.precio_venta, p.precio_compra, (p.stock * p.precio_venta) as valor_total_producto FROM lugares l LEFT JOIN productos p ON l.id = p.lugar_id AND p.activo = 1 ORDER BY l.nombre, p.nombre";
    $resultados = $pdo->query($sql_productos_lugar)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados as $row) {
        if (!isset($lugares_productos[$row['lugar_id']])) {
            $lugares_productos[$row['lugar_id']] = ['nombre' => $row['lugar'], 'productos' => []];
            $totales_lugar[$row['lugar_id']] = ['total_productos' => 0, 'total_stock' => 0, 'valor_total' => 0];
        }
        if ($row['producto_id']) {
            $lugares_productos[$row['lugar_id']]['productos'][] = $row;
            $totales_lugar[$row['lugar_id']]['total_productos']++;
            $totales_lugar[$row['lugar_id']]['total_stock'] += $row['stock'];
            $totales_lugar[$row['lugar_id']]['valor_total'] += $row['valor_total_producto'];
        }
    }
    $sql_sin_ubicacion_lista = "SELECT id as producto_id, nombre as producto, codigo, stock, precio_venta, precio_compra, (stock * precio_venta) as valor_total_producto FROM productos WHERE lugar_id IS NULL AND activo = 1 ORDER BY nombre";
    $productos_sin_ubicacion = $pdo->query($sql_sin_ubicacion_lista)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error al cargar análisis: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos por Ubicación - <?php echo htmlspecialchars(SISTEMA_NOMBRE); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-container { margin: 0 auto; max-width: 1200px; padding: 20px; }

        .page-header-section {
            background-color: #f8f9fa;
            padding: 20px 15px 15px 15px;
            border-bottom: 2px solid #dee2e6;
            flex-shrink: 0;
        }

        .fixed-footer {
            flex-shrink: 0;
            background-color: white;
            border-top: 2px solid #dee2e6;
            padding: 20px;
        }

        .scrollable-content {
            flex-grow: 1;
            overflow-y: auto;
            padding: 15px;
        }

        .summary-card {
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            height: 130px;
            transition: transform 0.2s;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .summary-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255, 255, 255, 0.4);
        }

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
        }

        .summary-card .card-body {
            padding: 15px;
            text-align: center;
        }

        .stat-icon {
            font-size: 2.2rem;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0 0 10px 0;
            line-height: 1.2;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            line-height: 1;
        }

        .location-section {
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border-radius: 10px;
        }

        .location-header {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }

        .location-totals {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid #dee2e6;
            padding: 15px 20px;
            font-size: 1.1rem;
        }

        .location-totals .total-item {
            font-weight: bold;
            font-size: 1.2rem;
        }

        .location-totals .total-value {
            color: #0d6efd;
            font-weight: 900;
            font-size: 1.3rem;
        }

        .product-row:hover {
            background-color: #f8f9fa;
        }

        .table {
            table-layout: fixed !important;
            width: 100% !important;
        }

        .table th:nth-child(1),
        .table td:nth-child(1) {
            width: 120px !important;
        }

        .table th:nth-child(3),
        .table td:nth-child(3) {
            width: 120px !important;
            text-align: center !important;
            vertical-align: middle !important;
        }

        .table th:nth-child(4),
        .table td:nth-child(4) {
            width: 140px !important;
            text-align: right !important;
        }

        .table th:nth-child(5),
        .table td:nth-child(5) {
            width: 140px !important;
            text-align: right !important;
        }

        .table th:nth-child(6),
        .table td:nth-child(6) {
            width: 120px !important;
            text-align: center !important;
        }

        .quantity-badge {
            background-color: #0d6efd !important;
            color: white;
            font-weight: bold;
            padding: 8px 14px;
            border-radius: 8px;
            min-width: 60px;
            text-align: center;
            display: inline-block;
            font-size: 0.95rem;
        }

        .quantity-column {
            width: 120px !important;
            text-align: center !important;
            vertical-align: middle !important;
            padding: 12px !important;
        }

        .no-products {
            color: #6c757d;
            font-style: italic;
            text-align: center;
            padding: 40px;
            font-size: 1.1rem;
        }

        .action-btn {
            border: none !important;
            font-weight: 600;
            padding: 12px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            display: inline-block;
            width: 100%;
            text-align: center;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .action-btn-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
            color: white;
        }

        .action-btn-success {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
            color: white;
        }

        .action-btn-info {
            background: linear-gradient(135deg, #0dcaf0 0%, #087990 100%);
            color: white;
        }

        .action-btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #d39e00 100%);
            color: #000;
        }
    </style>
</head>

<body>
    <!-- NAVBAR UNIFICADO -->
    <?php include '../../config/navbar_code.php'; ?>

    <div class="main-container">

    <div class="page-header-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Productos por Ubicación</h2>
            <div>
                <a href="productos.php" class="btn btn-outline-secondary me-2"><i class="bi bi-arrow-left me-1"></i>Volver</a>
                <a href="reportes.php" class="btn btn-outline-primary"><i class="bi bi-file-earmark-bar-graph me-2"></i>Reportes</a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="card summary-card bg-info text-white">
                    <div class="card-body"><i class="bi bi-geo-alt stat-icon"></i>
                        <p class="stat-label">Ubicaciones</p>
                        <div class="stat-number"><?php echo number_format($totales_generales['total_lugares']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="card summary-card bg-success text-white">
                    <div class="card-body"><i class="bi bi-box-seam stat-icon"></i>
                        <p class="stat-label">Total Productos</p>
                        <div class="stat-number"><?php echo number_format($totales_generales['total_productos']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="card summary-card bg-primary text-white">
                    <div class="card-body"><i class="bi bi-boxes stat-icon"></i>
                        <p class="stat-label">Stock Total</p>
                        <div class="stat-number"><?php echo number_format($totales_generales['total_stock']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="card summary-card bg-warning text-dark">
                    <div class="card-body"><i class="bi bi-currency-dollar stat-icon"></i>
                        <p class="stat-label">Valor Total</p>
                        <div class="stat-number"><?php echo formatCurrency($totales_generales['valor_total']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="scrollable-content">
        <?php if (isset($error)): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <?php foreach ($lugares_productos as $lugar_id => $lugar_data): ?>
            <?php if (!empty($lugar_data['productos'])): ?>
                <div class="card location-section">
                    <div class="location-header">
                        <h4 class="mb-0"><i class="bi bi-geo-alt-fill me-2"></i><?php echo htmlspecialchars($lugar_data['nombre']); ?></h4>
                    </div>
                    <div class="location-totals">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="total-item">Productos: <span class="total-value"><?php echo number_format($totales_lugar[$lugar_id]['total_productos']); ?></span></div>
                            </div>
                            <div class="col-md-4">
                                <div class="total-item">Stock Total: <span class="total-value"><?php echo number_format($totales_lugar[$lugar_id]['total_stock']); ?></span></div>
                            </div>
                            <div class="col-md-4">
                                <div class="total-item">Valor Total: <span class="total-value"><?php echo formatCurrency($totales_lugar[$lugar_id]['valor_total']); ?></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th class="quantity-column">Cantidad</th>
                                        <th class="text-end">Valor Unitario</th>
                                        <th class="text-end">Valor Total</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lugar_data['productos'] as $producto): ?>
                                        <tr class="product-row">
                                            <td><code><?php echo htmlspecialchars($producto['codigo'] ?: 'N/A'); ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($producto['producto']); ?></strong></td>
                                            <td class="quantity-column"><span class="quantity-badge"><?php echo number_format($producto['stock']); ?></span></td>
                                            <td class="text-end"><?php echo formatCurrency($producto['precio_venta']); ?></td>
                                            <td class="text-end"><strong><?php echo formatCurrency($producto['valor_total_producto']); ?></strong></td>
                                            <td class="text-center">
                                                <a href="producto_detalle.php?id=<?php echo $producto['producto_id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver detalles"><i class="bi bi-eye"></i></a>
                                                <a href="producto_form.php?id=<?php echo $producto['producto_id']; ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (!empty($productos_sin_ubicacion)): ?>
            <div class="card location-section">
                <div class="location-header" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);">
                    <h4 class="mb-0"><i class="bi bi-question-circle me-2"></i>Sin Ubicación</h4>
                </div>
                <div class="location-totals">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="total-item">Productos: <span class="total-value"><?php echo number_format(count($productos_sin_ubicacion)); ?></span></div>
                        </div>
                        <div class="col-md-4">
                            <div class="total-item">Stock Total: <span class="total-value"><?php echo number_format(array_sum(array_column($productos_sin_ubicacion, 'stock'))); ?></span></div>
                        </div>
                        <div class="col-md-4">
                            <div class="total-item">Valor Total: <span class="total-value"><?php echo formatCurrency(array_sum(array_column($productos_sin_ubicacion, 'valor_total_producto'))); ?></span></div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th class="quantity-column">Cantidad</th>
                                    <th class="text-end">Valor Unitario</th>
                                    <th class="text-end">Valor Total</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos_sin_ubicacion as $producto): ?>
                                    <tr class="product-row">
                                        <td><code><?php echo htmlspecialchars($producto['codigo'] ?: 'N/A'); ?></code></td>
                                        <td><strong><?php echo htmlspecialchars($producto['producto']); ?></strong></td>
                                        <td class="quantity-column"><span class="quantity-badge"><?php echo number_format($producto['stock']); ?></span></td>
                                        <td class="text-end"><?php echo formatCurrency($producto['precio_venta']); ?></td>
                                        <td class="text-end"><strong><?php echo formatCurrency($producto['valor_total_producto']); ?></strong></td>
                                        <td class="text-center">
                                            <a href="producto_detalle.php?id=<?php echo $producto['producto_id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver detalles"><i class="bi bi-eye"></i></a>
                                            <a href="producto_form.php?id=<?php echo $producto['producto_id']; ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="fixed-footer">
        <h5 class="mb-3"><i class="bi bi-lightning-charge me-2"></i>Acciones Rápidas</h5>
        <div class="row">
            <div class="col-md-3 mb-2 mb-md-0"><a href="productos.php" class="action-btn action-btn-primary"><i class="bi bi-list-ul me-2"></i>Todos los Productos</a></div>
            <div class="col-md-3 mb-2 mb-md-0"><a href="producto_form.php" class="action-btn action-btn-success"><i class="bi bi-plus-circle me-2"></i>Nuevo Producto</a></div>
            <div class="col-md-3 mb-2 mb-md-0"><a href="productos_por_categoria.php" class="action-btn action-btn-info"><i class="bi bi-tags me-2"></i>Por Categoría</a></div>
            <div class="col-md-3 mb-2 mb-md-0">
                <div class="dropdown">
                    <button class="action-btn action-btn-warning dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-file-earmark-excel me-2"></i>Exportar
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="reporte_completo_excel.php">
                                <i class="bi bi-file-earmark-excel me-2"></i>Reporte Completo Excel
                            </a></li>
                        <li><a class="dropdown-item" href="reporte_inventario_csv.php">
                                <i class="bi bi-file-earmark-spreadsheet me-2"></i>Reporte Completo CSV
                            </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>